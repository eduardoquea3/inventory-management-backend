<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'products_name_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('api_token', 'users_api_token_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_api_token_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_name_index');
        });
    }
};
