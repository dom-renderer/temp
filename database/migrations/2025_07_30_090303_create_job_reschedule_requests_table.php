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
        Schema::create('job_reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->dateTime('rescheduled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->boolean('status')->default(1)->comment('0 = Pending | 1 = Approved | 2 = Rejected');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_reschedule_requests');
    }
};
