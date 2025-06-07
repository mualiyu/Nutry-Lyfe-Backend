<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('username')->nullable()->unique();
            $table->string('phone');
            $table->string('user_type');
            $table->string('status');
            $table->boolean('isActive')->nullable();
            $table->string('photo')->nullable();
            $table->longText('description')->nullable();
            $table->string('my_ref_id')->nullable();

            $table->string('ref_id')->nullable();

            // vendor account detail
            $table->string('acct_name')->nullable();
            $table->string('acct_number')->nullable();
            $table->string('acct_type')->nullable();
            $table->string('bank')->nullable();

            // Package
            $table->string('package_id')->nullable();

            // Address
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->longText('address')->nullable();


            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
