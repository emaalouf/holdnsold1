<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('rejection_reason')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false);
            $table->string('ban_reason')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('suspended_until')->nullable();
            $table->string('suspension_reason')->nullable();
        });
    }

    public function down()
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'is_verified', 'rejection_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_banned', 'ban_reason', 'is_verified', 'suspended_until', 'suspension_reason']);
        });
    }
}; 