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
            $table->foreignId('item_id')->constrained()->onDelete('restrict')->index();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('restrict');
            $table->enum('payment_method', ['card', 'convenience_store']);
            $table->string('stripe_session_id')->unique()->nullable();
            $table->string('ship_postal_code');
            $table->string('ship_address');
            $table->string('ship_building')->nullable();
            $table->unsignedInteger('price_at_purchase');
            $table->enum('payment_status', ['pending', 'paid', 'canceled', 'expired']);
            $table->timestamps();
            $table->timestamp('reserved_until');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->index(['item_id', 'payment_status', 'reserved_until'], 'orders_item_status_reserved_idx');
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
