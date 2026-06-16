<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('photo_path')->nullable();
            $table->string('birth_place');
            $table->date('birth_date');
            $table->enum('marital_status', ['kawin', 'tidak kawin']);
            $table->integer('children_count')->default(0);
            $table->date('joined_at');
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('department_id')->constrained('departments');
            $table->enum('employment_type', ['pkwtt', 'pkwt', 'magang']);
            $table->enum('gender', ['pria', 'wanita']);
            $table->decimal('distance_km', 5, 2);
            $table->foreignId('district_id')->constrained('districts');
            $table->text('full_address');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('education_level'); // e.g., SD, SMP, SMA, S1
            $table->string('school_name');
            $table->integer('graduation_year');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
        Schema::dropIfExists('employees');
    }
};
