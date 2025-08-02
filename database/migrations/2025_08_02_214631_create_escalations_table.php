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
        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->integer('time')->default(1);
            $table->enum('time_type', ['MINUTE', 'HOUR', 'DAY'])->default('DAY');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('LOW');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->json('recipients')->nullable();
            $table->json('user_ids')->nullable();
            $table->json('departments')->nullable();
            $table->integer('level')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalations');
    }
};
