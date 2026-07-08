<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sales_orders, sales_returns, payment_ins, supplier_payments already
        // have created_by → users FK from prior migrations.
        // branch_transfers has no created_by column at all.
        // Nothing to do here — migration kept as a record of the audit finding.
    }

    public function down(): void
    {
        // No-op
    }
};
