<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\CatalogCache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return ApiResponse::success(CatalogCache::remember('dashboard', [], static fn () => [
            'products' => DB::table('products')->count(),
            'categories' => DB::table('categories')->count(),
            'low_stock' => DB::table('products')->where('stock', '<', config('inventory.low_stock_threshold'))->get(),
            'last_movements' => DB::table('stock_movements')->orderByDesc('created_at')->orderByDesc('id')->limit(20)->get(),
        ]));
    }

    public function health()
    {
        try {
            DB::select('SELECT 1');
            return ApiResponse::success(['status' => 'ok', 'database' => 'connected']);
        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error('SERVICE_UNAVAILABLE', 'Health check failed.', 500);
        }
    }
}
