<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->text('booking_id');
            $table->integer('user_id');
            $table->string('order_id');
            $table->string('product_id');
            $table->string('product_price');
            $table->string('quantity');
            $table->string('paypal_url');
            $table->string('currency');
            $table->float('amount_paid');
            $table->string('invoice_url');
            $table->string('payment_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
