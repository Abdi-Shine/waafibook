<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SystemBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:system-backup {--type=auto} {--company=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated system backup and enforce retention policies.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting System Archival Protocol...');
        
        try {
            $companyId = $this->option('company');
            $company = $companyId
                ? \App\Models\Company::withoutGlobalScopes()->find($companyId)
                : \App\Models\Company::withoutGlobalScopes()->first();

            if (!$company) {
                $this->error('Company configuration not discovered. Aborting.');
                return 1;
            }

            $filename = "backup-auto-" . now()->format('Y-m-d-H-i-s') . ".sql";
            $directory = storage_path('app/backups');
            if (!file_exists($directory)) mkdir($directory, 0755, true);
            $path = $directory . DIRECTORY_SEPARATOR . $filename;

            $dbConfig = config('database.connections.mysql');
            $mysqldumpPath = 'mysqldump';
            if (PHP_OS_FAMILY === 'Windows') {
                $possiblePaths = [
                    'C:\xampp\mysql\bin\mysqldump.exe',
                    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe'
                ];
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) { $mysqldumpPath = $p; break; }
                }
            }

            $command = sprintf(
                '%s --user=%s %s --host=%s %s > %s',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($dbConfig['username']),
                $dbConfig['password'] ? "--password=" . escapeshellarg($dbConfig['password']) : "",
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($path)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($path) && filesize($path) > 0) {
                $bytes = filesize($path);
                $sizeLabel = round($bytes / 1024 / 1024, 2) . " MB";
                \App\Models\Backup::create([
                    'company_id' => $company->id,
                    'filename' => $filename,
                    'path' => 'backups/' . $filename,
                    'size' => $bytes,
                    'type' => $this->option('type'),
                    'status' => 'success'
                ]);
                $this->info("Snapshot successfully archived: {$filename} ({$sizeLabel})");

                // Enforce Retention Policy
                $retentionDays = $company->backup_retention ?? 30;
                $expiryDate = now()->subDays($retentionDays);
                
                $expiredBackups = \App\Models\Backup::where('created_at', '<', $expiryDate)->get();
                foreach ($expiredBackups as $expired) {
                    $oldPath = storage_path('app/' . $expired->path);
                    if (file_exists($oldPath)) @unlink($oldPath);
                    $expired->delete();
                    $this->warn("Purged expired archive: {$expired->filename}");
                }
            } else {
                if (file_exists($path)) @unlink($path);
                throw new \Exception("Archival failed with exit code {$returnVar} or file was empty.");
            }

        } catch (\Exception $e) {
            $this->error("Protocol Error: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("System Backup Failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
