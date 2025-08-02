<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_spent_on_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id');
            $table->foreignId('technician_id');
            $table->date('date')->nullable();
            $table->timestamp('punch_in_at')->nullable();
            $table->timestamp('punch_out_at')->nullable();
            $table->enum('status', ['PUNCHED_IN', 'PUNCHED_OUT'])->default('PUNCHED_OUT');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['job_id', 'technician_id', 'punch_in_at'], 'unique_attendance_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_spent_on_jobs');
    }
};
