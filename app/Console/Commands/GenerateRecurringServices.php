<?php

namespace App\Console\Commands;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRecurringServices extends Command
{
    protected $signature   = 'services:generate-recurring';
    protected $description = 'Auto-generate service orders for active recurring schedules due today or past';

    public function handle(): int
    {
        $due = ServiceSchedule::with('customer')
            ->where('status', 'active')
            ->whereDate('next_due_date', '<=', now()->toDateString())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No recurring services due today.');
            return self::SUCCESS;
        }

        $generated = 0;

        foreach ($due as $schedule) {
            try {
                DB::transaction(function () use ($schedule, &$generated) {
                    $order = ServiceOrder::create([
                        'company_id'     => $schedule->company_id,
                        'order_number'   => ServiceOrder::nextOrderNumber($schedule->company_id),
                        'customer_id'    => $schedule->customer_id,
                        'schedule_id'    => $schedule->id,
                        'status'         => 'pending',
                        'priority'       => 'normal',
                        'title'          => $schedule->title,
                        'scheduled_date' => $schedule->next_due_date->toDateString(),
                        'notes'          => "Auto-generated from recurring schedule: {$schedule->title}",
                        'created_by'     => null,
                    ]);

                    $subtotal = 0;
                    foreach ((array)$schedule->template_items as $item) {
                        $qty       = (float)($item['quantity'] ?? 1);
                        $price     = (float)($item['unit_price'] ?? 0);
                        $itemTotal = $qty * $price;
                        $subtotal += $itemTotal;

                        ServiceOrderItem::create([
                            'service_order_id' => $order->id,
                            'product_id'       => $item['product_id'] ?? null,
                            'description'      => $item['description'] ?? $schedule->title,
                            'quantity'         => $qty,
                            'unit_price'       => $price,
                            'discount_pct'     => 0,
                            'total'            => $itemTotal,
                        ]);
                    }

                    $order->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);

                    $schedule->advanceNextDueDate();
                    $generated++;
                });
            } catch (\Throwable $e) {
                $this->error("Failed for schedule #{$schedule->id}: " . $e->getMessage());
            }
        }

        $this->info("Generated {$generated} service order(s).");
        return self::SUCCESS;
    }
}
