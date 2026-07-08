<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    /**
     * Cache TTL in seconds (default 1 hour)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache TTL for stock levels (shorter, 5 minutes for frequent updates)
     */
    private const STOCK_TTL = 300;

    /**
     * Cache TTL for product catalog (longer, 2 hours)
     */
    private const PRODUCT_CATALOG_TTL = 7200;

    /**
     * Get product by ID with caching
     *
     * @param int $productId
     * @return Product|null
     */
    public function getProduct(int $productId): ?Product
    {
        $cacheKey = "product:{$productId}";

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($productId) {
            return Product::with(['brand', 'category', 'baseUnit', 'variations'])->find($productId);
        });
    }

    /**
     * Get product stock level with caching
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return int
     */
    public function getProductStock(int $productId, ?int $warehouseId = null): int
    {
        $cacheKey = $warehouseId 
            ? "product_stock:{$productId}:warehouse:{$warehouseId}"
            : "product_stock:{$productId}:all";

        return Cache::remember($cacheKey, self::STOCK_TTL, function () use ($productId, $warehouseId) {
            $query = Batch::where('product_id', $productId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>', today());
                });

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            return (int) $query->sum('remaining_quantity');
        });
    }

    /**
     * Get product catalog with pagination (cached)
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getProductCatalog(array $filters = [], int $perPage = 50, int $page = 1): array
    {
        $cacheKey = 'product_catalog:' . md5(serialize($filters) . ":{$perPage}:{$page}");

        return Cache::remember($cacheKey, self::PRODUCT_CATALOG_TTL, function () use ($filters, $perPage, $page) {
            $query = Product::with(['brand', 'category', 'baseUnit'])
                ->where('status', 'active');

            // Apply filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (!empty($filters['brand_id'])) {
                $query->where('brand_id', $filters['brand_id']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ];
        });
    }

    /**
     * Get low stock products (cached for dashboard)
     *
     * @param int $threshold
     * @param int|null $warehouseId
     * @return array
     */
    public function getLowStockProducts(int $threshold = 10, ?int $warehouseId = null): array
    {
        $cacheKey = $warehouseId
            ? "low_stock:threshold:{$threshold}:warehouse:{$warehouseId}"
            : "low_stock:threshold:{$threshold}:all";

        return Cache::remember($cacheKey, self::STOCK_TTL, function () use ($threshold, $warehouseId) {
            $query = DB::table('products')
                ->select([
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(batches.remaining_quantity) as total_stock')
                ])
                ->join('batches', 'products.id', '=', 'batches.product_id')
                ->where('products.status', 'active')
                ->where('products.track_stock', true)
                ->where('batches.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('batches.expiry_date')
                      ->orWhere('batches.expiry_date', '>', today());
                })
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->havingRaw('SUM(batches.remaining_quantity) <= ?', [$threshold]);

            if ($warehouseId) {
                $query->where('batches.warehouse_id', $warehouseId);
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Get near-expiry batches (cached for alerts)
     *
     * @param int $daysThreshold
     * @param int|null $warehouseId
     * @return array
     */
    public function getNearExpiryBatches(int $daysThreshold = 90, ?int $warehouseId = null): array
    {
        $cacheKey = $warehouseId
            ? "near_expiry:days:{$daysThreshold}:warehouse:{$warehouseId}"
            : "near_expiry:days:{$daysThreshold}:all";

        return Cache::remember($cacheKey, self::STOCK_TTL, function () use ($daysThreshold, $warehouseId) {
            $query = Batch::with(['product', 'warehouse'])
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [today(), today()->addDays($daysThreshold)])
                ->where('remaining_quantity', '>', 0)
                ->orderBy('expiry_date', 'asc');

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Invalidate product cache
     *
     * @param int $productId
     * @return void
     */
    public function invalidateProduct(int $productId): void
    {
        Cache::forget("product:{$productId}");
        
        // Invalidate stock cache for all warehouses
        Cache::forget("product_stock:{$productId}:all");
        
        // Invalidate catalog pages (this is a blunt instrument, consider using cache tags in production)
        // For now, we'll use a wildcard pattern if using Redis
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->forgetByPattern("product_catalog:*");
            $this->forgetByPattern("low_stock:*");
        }
    }

    /**
     * Invalidate stock cache
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return void
     */
    public function invalidateProductStock(int $productId, ?int $warehouseId = null): void
    {
        if ($warehouseId) {
            Cache::forget("product_stock:{$productId}:warehouse:{$warehouseId}");
        } else {
            // Invalidate all warehouse caches for this product
            Cache::forget("product_stock:{$productId}:all");
        }
        
        // Invalidate low stock cache
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->forgetByPattern("low_stock:*");
        }
    }

    /**
     * Invalidate batch/expiry caches
     *
     * @param int|null $warehouseId
     * @return void
     */
    public function invalidateBatchCache(?int $warehouseId = null): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $pattern = $warehouseId 
                ? "near_expiry:*:warehouse:{$warehouseId}"
                : "near_expiry:*";
            $this->forgetByPattern($pattern);
        }
    }

    /**
     * Clear all product-related caches (use sparingly, typically during bulk imports)
     *
     * @return void
     */
    public function clearAllProductCaches(): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->forgetByPattern("product:*");
            $this->forgetByPattern("product_stock:*");
            $this->forgetByPattern("product_catalog:*");
            $this->forgetByPattern("low_stock:*");
            $this->forgetByPattern("near_expiry:*");
        } else {
            // Fallback: flush entire cache (not recommended in production)
            Cache::flush();
        }
    }

    /**
     * Forget cache keys matching a pattern (Redis only)
     *
     * @param string $pattern
     * @return void
     */
    private function forgetByPattern(string $pattern): void
    {
        $redis = Cache::getStore()->connection();
        $prefix = Cache::getStore()->getPrefix();
        
        $keys = $redis->keys($prefix . $pattern);
        
        if (!empty($keys)) {
            // Remove prefix from keys before deleting
            $keysWithoutPrefix = array_map(function ($key) use ($prefix) {
                return str_replace($prefix, '', $key);
            }, $keys);
            
            Cache::deleteMultiple($keysWithoutPrefix);
        }
    }

    /**
     * Get cache statistics (useful for monitoring)
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $info = $redis->info('stats');
            
            return [
                'driver' => 'redis',
                'total_keys' => $redis->dbSize(),
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        }

        return [
            'driver' => config('cache.default'),
            'message' => 'Cache statistics not available for this driver',
        ];
    }

    /**
     * Calculate cache hit rate
     *
     * @param array $info
     * @return float
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }
}
