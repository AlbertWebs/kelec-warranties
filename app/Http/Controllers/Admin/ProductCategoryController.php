<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('products.view'), 403);

        $categories = ProductCategory::withCount('products')->orderBy('name')->paginate(20);

        return view('admin.product-categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50', 'unique:product_categories,code'],
            'description' => ['nullable', 'string'],
            'default_warranty_months' => ['required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        ProductCategory::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50', 'unique:product_categories,code,'.$productCategory->id],
            'description' => ['nullable', 'string'],
            'default_warranty_months' => ['required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productCategory->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);
        $productCategory->delete();

        return back()->with('success', 'Category deleted.');
    }
}
