<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRealDeliveryDateToRentals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dateTime('real_delivery_date')->after('delivery_date')->nullable();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('perpetual_terminated')->after('is_approved')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('real_delivery_date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('perpetual_terminated');
        });
    }
}
