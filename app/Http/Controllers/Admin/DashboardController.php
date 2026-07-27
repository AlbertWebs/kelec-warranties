<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\IntegrationFailure;
use App\Models\NotificationLog;
use App\Models\Warranty;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'total' => Warranty::count(),
            'active' => Warranty::where('status', WarrantyStatus::Active)->count(),
            'pending' => Warranty::where('status', WarrantyStatus::PendingVerification)->count(),
            'rejected' => Warranty::where('status', WarrantyStatus::Rejected)->count(),
            'expired' => Warranty::where('status', WarrantyStatus::Expired)->count(),
            'today' => Warranty::whereDate('created_at', today())->count(),
            'month' => Warranty::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'odoo_failures' => IntegrationFailure::where('status', 'pending')->count(),
            'sms_failures' => NotificationLog::where('channel', 'sms')->where('status', 'failed')->count(),
            'email_failures' => NotificationLog::where('channel', 'email')->where('status', 'failed')->count(),
        ];

        $validated = Warranty::whereNotNull('odoo_validated')->count();
        $validatedOk = Warranty::where('odoo_validated', true)->count();
        $stats['odoo_success_rate'] = $validated > 0 ? round(($validatedOk / $validated) * 100, 1) : 0;

        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $registrationsByMonth = Warranty::query()
            ->select(DB::raw("{$monthExpression} as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->pluck('total', 'month');

        $bySource = Warranty::query()
            ->select('purchase_source_id', DB::raw('count(*) as total'))
            ->groupBy('purchase_source_id')
            ->with('purchaseSource')
            ->get();

        $byStatus = Warranty::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = Warranty::with(['customer', 'product'])->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'registrationsByMonth', 'bySource', 'byStatus', 'recent'));
    }
}
