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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->boolean('all_day')->default(false);
            $table->dateTime('start');
            $table->dateTime('end');
            $table->integer('seller_id')->nullable();
            $table->boolean('repeat')->default(false);
            $table->enum('repeat_type', ['day', 'week', 'month', 'year'])->nullable();
            $table->integer('repeat_interval')->nullable();
            $table->integer('repeat_count')->nullable();
            $table->dateTime('repeat_until')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
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
        Schema::dropIfExists('events');
    }
};
