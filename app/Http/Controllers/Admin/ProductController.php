<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('products.view'), 403);

        $products = Product::with('category')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('sku', 'like', '%'.$request->q.'%')
                ->orWhere('model', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('products.manage'), 403);

        return view('admin.products.create', [
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);

        $data = $this->validated($request);
        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Request $request, Product $product): View
    {
        abort_unless($request->user()->can('products.manage'), 403);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);

        $product->update($this->validated($request, $product->id));

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->can('products.manage'), 403);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'product_code' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.($ignoreId ?? 'NULL')],
            'model' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'default_warranty_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'registration_grace_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
            'serial_tracking_enabled' => ['sometimes', 'boolean'],
            'manual_verification_allowed' => ['sometimes', 'boolean'],
            'receipt_required' => ['sometimes', 'boolean'],
            'warranty_terms' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['serial_tracking_enabled'] = $request->boolean('serial_tracking_enabled', true);
        $data['manual_verification_allowed'] = $request->boolean('manual_verification_allowed', true);
        $data['receipt_required'] = $request->boolean('receipt_required', false);
        $data['brand'] = $data['brand'] ?? 'K-Elec';

        return $data;
    }
}
