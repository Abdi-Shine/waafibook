<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_employees', function (Blueprint $table) {
            $table->foreignId('service_order_id')->constrained('service_orders')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('role', 50)->nullable(); // lead, assistant, inspector
            $table->timestamp('assigned_at')->useCurrent();
            $table->primary(['service_order_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_employees');
    }
};
