<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('payload');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_admin', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']));
    }
};
