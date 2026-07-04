<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('title');
            $table->string('frequency')->default('monthly'); // daily, weekly, biweekly, monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->string('status')->default('active'); // active, paused, ended
            $table->json('template_items')->nullable(); // [{product_id, description, quantity, unit_price}]
            $table->boolean('auto_invoice')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
