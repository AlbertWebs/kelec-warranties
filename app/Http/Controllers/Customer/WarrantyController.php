<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');
        $warranties = $customer->warranties()
            ->with(['product', 'purchaseSource'])
            ->latest('registration_date')
            ->paginate(15);

        return view('customer.warranties.index', compact('warranties', 'customer'));
    }

    public function show(Request $request, Warranty $warranty): View
    {
        abort_unless($warranty->customer_id === $request->user('customer')->id, 404);

        $warranty->load(['product', 'purchaseSource', 'dealer', 'claims']);

        return view('customer.warranties.show', compact('warranty'));
    }
}
