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
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->enum('type', ['INVENTORY', 'VENDOR'])->default('INVENTORY')->comment('INVENTORY = when technician use products from inventory/warehouse | VENDOR = when technicia use product from any vendor');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('description')->nullable();
            $table->double('amount')->nullable()->default(0);
            $table->double('quantity')->nullable()->default(0);
            $table->double('total')->nullable()->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};
