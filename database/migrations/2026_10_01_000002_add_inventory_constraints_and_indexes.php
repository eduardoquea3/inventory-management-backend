<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orphanProducts = DB::table('products as p')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->whereNotNull('p.category_id')->whereNull('c.id')->exists();
        $orphanMovements = DB::table('stock_movements as sm')
            ->leftJoin('products as p', 'p.id', '=', 'sm.product_id')
            ->whereNull('p.id')->exists();
        $orphanUsers = DB::table('stock_movements as sm')
            ->leftJoin('users as u', 'u.id', '=', 'sm.user_id')
            ->whereNotNull('sm.user_id')->whereNull('u.id')->exists();

        if ($orphanProducts || $orphanMovements || $orphanUsers) {
            throw new RuntimeException('Cannot add inventory foreign keys: orphan product/category/user references must be repaired first.');
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['status', 'name'], 'categories_status_name_index');
            $table->index(['created_at', 'id'], 'categories_created_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id', 'products_category_id_index');
            $table->index(['status', 'category_id'], 'products_status_category_index');
            $table->index('price', 'products_price_index');
            $table->index('stock', 'products_stock_index');
            $table->index(['created_at', 'id'], 'products_created_id_index');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'id'], 'stock_movements_product_id_id_index');
            $table->index(['created_at', 'id'], 'stock_movements_created_id_index');
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex('stock_movements_product_id_id_index');
            $table->dropIndex('stock_movements_created_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_status_category_index');
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_stock_index');
            $table->dropIndex('products_created_id_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_status_name_index');
            $table->dropIndex('categories_created_id_index');
        });
    }
};
