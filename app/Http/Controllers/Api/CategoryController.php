<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with('parent');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('name', 'asc')->paginate(15);

        return CategoryResource::collection($categories)->response();
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = Category::create($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created_category',
            'description' => "Created category {$category->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        $category->load('parent');

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $data = $request->validated();
        $category->update($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated_category',
            'description' => "Updated category {$category->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category): Response
    {
        // F5: block deletion when products reference the category.
        $productsCount = \App\Models\Product::where('category_id', $category->id)->count();
        if ($productsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete category with associated products. Reassign products first.',
                'products_count' => $productsCount,
            ], 422);
        }

        $category->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_category',
            'description' => "Deleted category {$category->name}",
            'module' => 'master_data',
            'resource_type' => Category::class,
            'resource_id' => $category->id,
            'event' => 'deleted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }

    /**
     * Return category tree with product counts for POS navigation.
     * Flat list structure (parent_id references) for easy rendering.
     */
    public function tree(Request $request): JsonResponse
    {
        // Cache the category tree because it powers POS navigation and product
        // forms on every page load. Invalidated when categories or active
        // products change (see the CategoryObserver and ProductObserver).
        $categories = \Illuminate\Support\Facades\Cache::remember(
            'categories:tree',
            now()->addMinutes(15),
            function () {
                $productCounts = DB::table('products')
                    ->select('category_id', DB::raw('COUNT(*) as count'))
                    ->where('status', 'active')
                    ->groupBy('category_id');

                return Category::withCount(['children'])
                    ->leftJoinSub($productCounts, 'p_counts', function ($join) {
                        $join->on('categories.id', '=', 'p_counts.category_id');
                    })
                    ->select([
                        'categories.id',
                        'categories.name',
                        'categories.slug',
                        'categories.parent_id',
                        'categories.is_active',
                        DB::raw('COALESCE(p_counts.count, 0) as products_count'),
                    ])
                    ->where('categories.is_active', true)
                    ->orderBy('categories.parent_id', 'asc')
                    ->orderBy('categories.name', 'asc')
                    ->get();
            }
        );

        return response()->json(['data' => $categories]);
    }
}
