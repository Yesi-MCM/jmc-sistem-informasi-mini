<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_allowance_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fare', 12, 2);
            $table->date('effective_start');
            $table->decimal('min_km', 5, 2);
            $table->decimal('max_km', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by', 'fk_ta_settings_creator')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('transport_allowance_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('period_year');
            $table->integer('period_month');
            $table->integer('total_recipients')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->enum('status', ['draft', 'calculated', 'locked'])->default('draft');
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->foreign('calculated_by', 'fk_ta_periods_calculator')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('calculated_at')->nullable();
            $table->unique(['period_year', 'period_month']);
            $table->timestamps();
        });

        Schema::create('transport_allowance_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transport_allowance_period_id');
            $table->foreign('transport_allowance_period_id', 'fk_ta_details_period')->references('id')->on('transport_allowance_periods')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id', 'fk_ta_details_emp')->references('id')->on('employees')->onDelete('cascade');
            $table->decimal('base_fare', 12, 2);
            $table->decimal('original_km', 5, 2);
            $table->integer('rounded_km');
            $table->integer('attendance_days');
            $table->decimal('nominal', 15, 2);
            $table->string('eligibility_status'); // e.g., 'eligible', 'ineligible_distance', 'ineligible_presence', 'ineligible_employment_type'
            $table->text('calculation_note')->nullable();
            $table->unique(['transport_allowance_period_id', 'employee_id'], 'allowance_period_employee_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_allowance_details');
        Schema::dropIfExists('transport_allowance_periods');
        Schema::dropIfExists('transport_allowance_settings');
    }
};
