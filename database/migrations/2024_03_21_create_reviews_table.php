<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('auction_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('rating')->unsigned()->between(1, 5);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Prevent multiple reviews from same user for same auction/seller
            $table->unique(['reviewer_id', 'user_id', 'auction_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}; 