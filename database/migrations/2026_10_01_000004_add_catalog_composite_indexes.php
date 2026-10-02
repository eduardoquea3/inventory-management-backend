<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'status', 'created_at', 'id'], 'products_category_status_created_index');
            $table->index(['status', 'created_at', 'id'], 'products_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_status_created_index');
            $table->dropIndex('products_status_created_index');
        });
    }
};
