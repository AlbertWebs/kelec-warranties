<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseSourceType;
use App\Http\Controllers\Controller;
use App\Models\PurchaseSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseSourceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('purchase_sources.manage') || $request->user()->can('dealers.view'), 403);

        return view('admin.purchase-sources.index', [
            'purchaseSources' => PurchaseSource::orderBy('sort_order')->get(),
            'types' => PurchaseSourceType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('purchase_sources.manage'), 403);
        PurchaseSource::create($this->validated($request));

        return back()->with('success', 'Purchase source created.');
    }

    public function update(Request $request, PurchaseSource $purchaseSource): RedirectResponse
    {
        abort_unless($request->user()->can('purchase_sources.manage'), 403);
        $purchaseSource->update($this->validated($request, $purchaseSource->id));

        return back()->with('success', 'Purchase source updated.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:purchase_sources,code,'.($id ?? 'NULL')],
            'type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_dealer' => ['sometimes', 'boolean'],
            'requires_branch' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['requires_dealer'] = $request->boolean('requires_dealer', false);
        $data['requires_branch'] = $request->boolean('requires_branch', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
