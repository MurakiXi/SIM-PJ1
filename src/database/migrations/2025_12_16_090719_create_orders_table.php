<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('restrict')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('restrict');
            $table->enum('payment_method', ['card', 'convenience_store']);
            $table->string('stripe_session_id')->unique();
            $table->string('ship_postal_code');
            $table->string('ship_address');
            $table->string('ship_building')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
