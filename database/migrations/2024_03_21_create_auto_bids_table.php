<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('auto_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->decimal('max_amount', 10, 2);
            $table->decimal('bid_increment', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Prevent multiple auto-bids for same user and auction
            $table->unique(['user_id', 'auction_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('auto_bids');
    }
}; 