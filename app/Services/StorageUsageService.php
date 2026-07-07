<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

class StorageUsageService
{
    /**
     * Total bytes used on disk for a company (logos + photos + backups).
     */
    public static function usedBytes(int $companyId): int
    {
        $bytes = 0;

        // Company logo
        $company = Company::withoutGlobalScopes()->find($companyId);
        if ($company?->logo) {
            $path = public_path($company->logo);
            if (file_exists($path)) {
                $bytes += filesize($path);
            }
        }

        // Employee / user photos
        $photos = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('photo')
            ->pluck('photo');

        foreach ($photos as $photo) {
            $path = public_path('upload/admin_images/' . $photo);
            if (file_exists($path)) {
                $bytes += filesize($path);
            }
        }

        // Backups
        $backups = Backup::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('path');

        foreach ($backups as $relPath) {
            $path = storage_path('app/' . $relPath);
            if (file_exists($path)) {
                $bytes += filesize($path);
            }
        }

        return $bytes;
    }

    public static function usedGB(int $companyId): float
    {
        return round(self::usedBytes($companyId) / (1024 ** 3), 3);
    }

    /**
     * Storage limit in GB from the company's active subscription plan.
     * Returns 999 (unlimited) when no plan or plan has no limit.
     */
    public static function limitGB(int $companyId): float
    {
        $sub = Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('company_id', $companyId)
            ->whereIn('status', ['active', 'trial'])
            ->latest('id')
            ->first();

        return (float) ($sub?->plan?->storage_limit_gb ?? 999);
    }

    public static function isOverStorageLimit(int $companyId): bool
    {
        $limit = self::limitGB($companyId);
        if ($limit >= 999) return false;
        return self::usedGB($companyId) >= $limit;
    }

    /**
     * Max users from the company's active subscription plan.
     */
    public static function maxUsers(int $companyId): int
    {
        $sub = Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('company_id', $companyId)
            ->whereIn('status', ['active', 'trial'])
            ->latest('id')
            ->first();

        return (int) ($sub?->plan?->max_users ?? 999);
    }

    public static function usedUsers(int $companyId): int
    {
        return User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count();
    }

    public static function isOverUserLimit(int $companyId): bool
    {
        $max = self::maxUsers($companyId);
        if ($max >= 999) return false;
        return self::usedUsers($companyId) >= $max;
    }
}
