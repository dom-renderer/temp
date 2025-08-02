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
        Schema::create('job', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('assigner_id')->nullable();
            $table->string('title')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_dial_code')->nullable();
            $table->string('contact_phone_number')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('email')->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('location_url')->nullable();
            
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->dateTime('opening_date')->nullable();
            $table->dateTime('visiting_date')->nullable();
            $table->dateTime('opened_date')->nullable();
            $table->dateTime('visited_date')->nullable();
            $table->enum('status', ['PENDING', 'INPROGRESS', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->double('cancellation_amount')->default(0);
            $table->text('cancellation_note')->nullable();
            $table->boolean('requires_deposit')->default(false);
            $table->enum('deposit_type', ['PERCENT', 'FIX'])->default('FIX');
            $table->double('deposit_amount')->default(0);
            $table->double('grand_total')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job');
    }
};
