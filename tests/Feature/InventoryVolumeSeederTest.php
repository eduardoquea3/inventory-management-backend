<?php

namespace Tests\Feature;

use Database\Seeders\InventoryVolumeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryVolumeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_volume_seed_meets_minimum_counts_and_keeps_product_stock_coherent(): void
    {
        $seeder = new InventoryVolumeSeeder();
        $seeder->run();

        $this->assertSame(100, DB::table('categories')->where('name', 'like', 'Volume Category %')->count());
        $this->assertSame(10000, DB::table('products')->where('name', 'like', 'Volume Product %')->count());
        $this->assertSame(30000, DB::table('stock_movements')->where('reason', 'Deterministic volume seed movement')->count());

        $inconsistent = DB::table('products as p')
            ->join('stock_movements as sm', 'sm.product_id', '=', 'p.id')
            ->where('p.name', 'like', 'Volume Product %')
            ->groupBy('p.id', 'p.stock')
            ->havingRaw('p.stock != SUM(CASE WHEN sm.type = ? THEN sm.quantity ELSE -sm.quantity END)', ['entrada'])
            ->count();
        $this->assertSame(0, $inconsistent);

        $seeder->run();
        $this->assertSame(10000, DB::table('products')->where('name', 'like', 'Volume Product %')->count());
        $this->assertSame(30000, DB::table('stock_movements')->where('reason', 'Deterministic volume seed movement')->count());
    }
}
