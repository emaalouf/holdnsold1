<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->timestamps();

            $table->index(['auction_id', 'platform']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_shares');
    }
}; 