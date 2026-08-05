<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('claims.view'), 403);

        $claims = WarrantyClaim::query()
            ->with(['customer', 'warranty'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.claims.index', [
            'claims' => $claims,
            'statuses' => ClaimStatus::cases(),
        ]);
    }

    public function show(Request $request, WarrantyClaim $claim): View
    {
        abort_unless($request->user()->can('claims.view'), 403);

        $claim->load(['customer', 'warranty.product']);

        return view('admin.claims.show', [
            'claim' => $claim,
            'statuses' => ClaimStatus::cases(),
        ]);
    }

    public function update(Request $request, WarrantyClaim $claim): RedirectResponse
    {
        abort_unless($request->user()->can('claims.manage'), 403);
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ClaimStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $claim->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $claim->admin_notes,
        ]);

        return back()->with('success', 'Claim updated.');
    }
}
