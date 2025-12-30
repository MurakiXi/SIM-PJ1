<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('restrict');
            $table->string('name')->index();
            $table->string('brand')->nullable();
            $table->text('description');
            $table->unsignedInteger('price');
            $table->string('image_path');
            $table->enum('status', ['on_sale', 'processing', 'sold'])->default('on_sale');
            $table->unsignedTinyInteger('condition');
            $table->timestamp('processing_expires_at')->nullable();
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
        Schema::dropIfExists('items');
    }
}
