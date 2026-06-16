<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_filename');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('attendance_import_id')->nullable()->constrained('attendance_imports')->onDelete('set null');
            $table->date('attendance_date');
            $table->time('checkin_at')->nullable();
            $table->time('checkout_at')->nullable();
            $table->string('checkin_location')->nullable();
            $table->string('checkout_location')->nullable();
            $table->enum('attendance_type', ['hadir', 'cuti', 'izin', 'unpaid_leave'])->default('hadir');
            $table->decimal('duration_hours', 4, 1)->default(0.0);
            $table->enum('status', ['terpenuhi', 'tidak_terpenuhi'])->default('tidak_terpenuhi');
            $table->enum('verification_status', ['Disetujui', 'Ditolak'])->default('Disetujui');
            $table->enum('verified_by_role', ['Lead', 'Manager', 'HRD'])->default('HRD');
            $table->text('remarks')->nullable();
            $table->unique(['employee_id', 'attendance_date']);
            $table->timestamps();
        });

        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->decimal('hadir', 4, 1)->default(0.0); // sum of daily attendance weights (1.0 or 0.5 or 0.0)
            $table->decimal('cuti', 4, 1)->default(0.0);
            $table->decimal('kuota_cuti', 4, 1)->default(12.0); // default quota
            $table->decimal('izin', 4, 1)->default(0.0);
            $table->decimal('kuota_izin', 4, 1)->default(5.0);
            $table->decimal('unpaid_leave', 4, 1)->default(0.0);
            $table->decimal('kuota_unpaid_leave', 4, 1)->default(10.0);
            $table->enum('status_hadir', ['Terpenuhi', 'Tidak terpenuhi'])->default('Tidak terpenuhi');
            $table->timestamp('calculated_at')->nullable();
            $table->unique(['employee_id', 'period_year', 'period_month']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_imports');
    }
};
