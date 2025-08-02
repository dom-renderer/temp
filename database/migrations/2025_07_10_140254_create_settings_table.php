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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('theme_color')->nullable()->default('#28304e');
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->unsignedBigInteger('local_country_for_tax')->nullable()->comment('Country ID');
            $table->double('local_tax')->nullable()->default(0)->comment('0 to 100%');
            $table->double('overseas_tax')->nullable()->default(0)->comment('0 to 100%');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
