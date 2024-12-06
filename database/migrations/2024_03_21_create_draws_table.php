<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->decimal('entry_fee', 10, 2);
            $table->integer('max_entries')->nullable();
            $table->timestamp('draw_date')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });

        Schema::create('draw_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_transaction_id')
                  ->nullable();
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['draw_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('draw_entries');
        Schema::dropIfExists('draws');
    }
}; 