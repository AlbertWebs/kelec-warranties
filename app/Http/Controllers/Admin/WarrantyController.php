<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseSource;
use App\Models\Warranty;
use App\Services\NotificationDispatcher;
use App\Services\WarrantyAdminService;
use App\Services\WarrantyQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarrantyController extends Controller
{
    public function __construct(
        protected WarrantyQueryService $queryService,
        protected WarrantyAdminService $adminService,
        protected NotificationDispatcher $notificationDispatcher,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Warranty::class);

        $warranties = $this->queryService->adminSearch($request->all());

        return view('admin.warranties.index', [
            'warranties' => $warranties,
            'filters' => $request->all(),
            'products' => Product::orderBy('name')->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'purchaseSources' => PurchaseSource::orderBy('sort_order')->get(),
            'dealers' => Dealer::orderBy('name')->get(),
            'statuses' => WarrantyStatus::cases(),
            'pendingCount' => Warranty::query()
                ->whereIn('status', [
                    WarrantyStatus::PendingVerification->value,
                    WarrantyStatus::UnderReview->value,
                ])
                ->count(),
        ]);
    }

    public function pending(): View
    {
        $this->authorize('viewAny', Warranty::class);

        return view('admin.warranties.pending', [
            'warranties' => $this->queryService->pendingVerification(),
        ]);
    }

    public function show(Warranty $warranty): View
    {
        $this->authorize('view', $warranty);

        $warranty->load([
            'customer',
            'product.category',
            'purchaseSource',
            'dealer',
            'approver',
            'statusHistories.changedBy',
            'documents',
            'notes.user',
            'consents',
            'notificationLogs',
            'informationRequests',
            'claims',
        ]);

        return view('admin.warranties.show', compact('warranty'));
    }

    public function update(Request $request, Warranty $warranty): RedirectResponse
    {
        $this->authorize('update', $warranty);

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'purchase_source_id' => ['nullable', 'exists:purchase_sources,id'],
            'dealer_id' => ['nullable', 'exists:dealers,id'],
            'product_name' => ['nullable', 'string', 'max:150'],
            'product_model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:150'],
            'purchase_date' => ['nullable', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'warranty_duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'warranty_start_date' => ['nullable', 'date'],
            'warranty_expiry_date' => ['nullable', 'date', 'after_or_equal:warranty_start_date'],
            'customer_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $this->adminService->update($warranty, $data, $request->user());

        return back()->with('success', 'Warranty updated successfully.');
    }

    public function approve(Request $request, Warranty $warranty): RedirectResponse
    {
        $this->authorize('approve', $warranty);

        $data = $request->validate([
            'warranty_start_date' => ['nullable', 'date'],
        ]);

        $start = ! empty($data['warranty_start_date']) ? now()->parse($data['warranty_start_date']) : null;
        $this->adminService->approve($warranty, $request->user(), $start);

        return back()->with('success', 'Warranty approved and activated.');
    }

    public function reject(Request $request, Warranty $warranty): RedirectResponse
    {
        $this->authorize('reject', $warranty);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->adminService->reject($warranty, $request->user(), $data['rejection_reason']);

        return back()->with('success', 'Warranty rejected.');
    }

    public function addNote(Request $request, Warranty $warranty): RedirectResponse
    {
        $this->authorize('update', $warranty);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $this->notificationDispatcher->addNote(
            $warranty,
            $request->user(),
            $data['body'],
            $request->boolean('is_internal', true)
        );

        return back()->with('success', 'Note added.');
    }

    public function resend(Request $request, Warranty $warranty): RedirectResponse
    {
        $this->authorize('resendNotification', $warranty);

        $type = $warranty->status === WarrantyStatus::Active
            ? 'warranty_activated'
            : ($warranty->status === WarrantyStatus::PendingVerification ? 'warranty_pending_verification' : 'warranty_lookup');

        $this->notificationDispatcher->resend($warranty, $type);

        return back()->with('success', 'Notification sent to the customer.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Warranty::class);

        $warranties = $this->queryService->adminSearch($request->all(), 10000);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="warranties-export.csv"',
        ];

        return response()->stream(function () use ($warranties) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Reference', 'Customer', 'Mobile', 'Product', 'Model', 'Serial', 'Source',
                'Purchase Date', 'Expiry', 'Status', 'Registration Source', 'Created',
            ]);

            foreach ($warranties as $warranty) {
                fputcsv($handle, [
                    $warranty->reference,
                    $warranty->customer?->full_name,
                    $warranty->customer?->mobile_normalized,
                    $warranty->displayProductName(),
                    $warranty->displayModel(),
                    $warranty->serial_number,
                    $warranty->purchaseSource?->name,
                    optional($warranty->purchase_date)?->toDateString(),
                    optional($warranty->warranty_expiry_date)?->toDateString(),
                    $warranty->status instanceof WarrantyStatus ? $warranty->status->value : $warranty->status,
                    $warranty->registration_source instanceof \App\Enums\RegistrationSource
                        ? $warranty->registration_source->value
                        : $warranty->registration_source,
                    optional($warranty->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
