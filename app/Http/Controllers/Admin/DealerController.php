<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('dealers.view'), 403);

        $dealers = Dealer::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.dealers.index', compact('dealers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('dealers.manage'), 403);
        Dealer::create($this->validated($request));

        return back()->with('success', 'Dealer created.');
    }

    public function update(Request $request, Dealer $dealer): RedirectResponse
    {
        abort_unless($request->user()->can('dealers.manage'), 403);
        $dealer->update($this->validated($request, $dealer->id));

        return back()->with('success', 'Dealer updated.');
    }

    public function destroy(Request $request, Dealer $dealer): RedirectResponse
    {
        abort_unless($request->user()->can('dealers.manage'), 403);
        $dealer->delete();

        return back()->with('success', 'Dealer deactivated/deleted.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dealer_code' => ['nullable', 'string', 'max:50', 'unique:dealers,dealer_code,'.($id ?? 'NULL')],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'county' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'physical_location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_authorised' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_authorised'] = $request->boolean('is_authorised', true);

        return $data;
    }
}
