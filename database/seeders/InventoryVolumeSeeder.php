<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\CatalogCache;

class InventoryVolumeSeeder extends Seeder
{
    private const CATEGORY_COUNT = 100;
    private const PRODUCT_COUNT = 10000;

    public function run(): void
    {
        DB::transaction(function () {
            if (DB::table('products')->where('name', 'like', 'Volume Product %')->exists()) {
                return;
            }

            $now = now();
            DB::table('users')->updateOrInsert(
                ['email' => 'admin@legacy.test'],
                ['name' => 'Admin Legacy', 'password' => Hash::make('password'), 'api_token' => null, 'updated_at' => $now, 'created_at' => $now]
            );
            $userId = (int) DB::table('users')->where('email', 'admin@legacy.test')->value('id');

            $categoryNames = [];
            for ($number = 1; $number <= self::CATEGORY_COUNT; $number++) {
                $categoryNames[] = sprintf('Volume Category %04d', $number);
            }
            $knownCategories = DB::table('categories')->whereIn('name', $categoryNames)->pluck('id', 'name')->all();
            $missingCategories = [];
            foreach ($categoryNames as $name) {
                if (!isset($knownCategories[$name])) {
                    $missingCategories[] = ['name' => $name, 'description' => 'Deterministic performance-test category', 'status' => 1, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            foreach (array_chunk($missingCategories, 250) as $chunk) {
                DB::table('categories')->insert($chunk);
            }
            $categoryIds = DB::table('categories')->whereIn('name', $categoryNames)->orderBy('id')->pluck('id')->all();

            $chunkSize = DB::connection()->getDriverName() === 'sqlite' ? 80 : 400;
            for ($start = 1; $start <= self::PRODUCT_COUNT; $start += $chunkSize) {
                $end = min($start + $chunkSize - 1, self::PRODUCT_COUNT);
                $products = [];
                for ($number = $start; $number <= $end; $number++) {
                    $products[] = [
                        'name' => sprintf('Volume Product %05d', $number),
                        'description' => 'Deterministic volume-seed product',
                        'price' => number_format(5 + (($number * 37) % 25000) / 100, 2, '.', ''),
                        'stock' => 102,
                        'category_id' => $categoryIds[($number - 1) % count($categoryIds)],
                        'status' => $number % 10 === 0 ? 0 : 1,
                        'created_at' => $now->copy()->subDays($number % 365),
                        'updated_at' => $now,
                    ];
                }
                DB::table('products')->insert($products);

                $names = array_column($products, 'name');
                $insertedProducts = DB::table('products')->whereIn('name', $names)->get(['id', 'name']);
                $movements = [];
                foreach ($insertedProducts as $product) {
                    foreach ([['entrada', 100], ['entrada', 5], ['salida', 3]] as [$type, $quantity]) {
                        $movements[] = [
                            'product_id' => $product->id,
                            'type' => $type,
                            'quantity' => $quantity,
                            'reason' => 'Deterministic volume seed movement',
                            'user_id' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                foreach (array_chunk($movements, 400) as $movementChunk) {
                    DB::table('stock_movements')->insert($movementChunk);
                }
            }
        });
        CatalogCache::invalidate();
    }
}
