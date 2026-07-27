<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $warranty->reference }} Certificate</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
        h1 { color: #b91c1c; }
        .grid td { padding: 8px 0; vertical-align: top; }
        .label { color: #64748b; font-size: 12px; }
        .value { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>K-Elec Warranty Certificate</h1>
    <p>Reference: <strong>{{ $warranty->reference }}</strong></p>
    <table class="grid" width="100%">
        <tr><td><div class="label">Customer</div><div class="value">{{ $warranty->customer->full_name }}</div></td><td><div class="label">Status</div><div class="value">{{ $warranty->status->label() }}</div></td></tr>
        <tr><td><div class="label">Product</div><div class="value">{{ $warranty->displayProductName() }}</div></td><td><div class="label">Model</div><div class="value">{{ $warranty->displayModel() ?? '—' }}</div></td></tr>
        <tr><td><div class="label">Serial number</div><div class="value">{{ $warranty->serial_number }}</div></td><td><div class="label">Purchase source</div><div class="value">{{ $warranty->purchaseSource?->name ?? '—' }}</div></td></tr>
        <tr><td><div class="label">Purchase date</div><div class="value">{{ optional($warranty->purchase_date)->format('d M Y') ?? '—' }}</div></td><td><div class="label">Start / Expiry</div><div class="value">{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }} / {{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? '—' }}</div></td></tr>
    </table>
    <p style="margin-top: 24px; font-size: 12px;">Lookup: {{ route('warranty.lookup', ['reference' => $warranty->reference]) }} (mobile verification required)</p>
    <p style="font-size: 12px;">Warranty Terms: {{ route('warranty-terms') }}</p>
</body>
</html>
