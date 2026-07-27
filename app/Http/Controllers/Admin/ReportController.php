<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $summary = [
            'all' => Warranty::count(),
            'active' => Warranty::where('status', WarrantyStatus::Active)->count(),
            'pending' => Warranty::where('status', WarrantyStatus::PendingVerification)->count(),
            'rejected' => Warranty::where('status', WarrantyStatus::Rejected)->count(),
            'expired' => Warranty::where('status', WarrantyStatus::Expired)->count(),
            'marketing_yes' => Warranty::where('marketing_consent', true)->count(),
            'marketing_no' => Warranty::where('marketing_consent', false)->count(),
            'expiring_30' => Warranty::where('status', WarrantyStatus::Active)
                ->whereBetween('warranty_expiry_date', [now(), now()->addDays(30)])
                ->count(),
        ];

        return view('admin.reports.index', compact('summary'));
    }
}
