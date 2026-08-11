<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $warranty->reference }} Certificate</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            background: #f8fafc;
            font-size: 12px;
            line-height: 1.45;
        }
        .page {
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .accent {
            height: 5px;
            background: #dc2626;
        }
        .hero {
            padding: 18px 22px 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff7f7;
        }
        .eyebrow {
            font-size: 10px;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: bold;
            margin-bottom: 6px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: #111827;
        }
        .subtitle {
            margin-top: 6px;
            color: #475569;
            font-size: 12px;
        }
        .content {
            padding: 18px 22px 20px;
        }
        .ref-wrap {
            border: 1px solid #fecaca;
            background: #fff1f2;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .ref-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9f1239;
            font-weight: bold;
        }
        .ref-value {
            margin-top: 4px;
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }
        .status-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: #e2e8f0;
            color: #334155;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 -8px;
        }
        .summary td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            background: #f8fafc;
        }
        .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .value {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }
        .support {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #334155;
            font-size: 11px;
        }
        .foot {
            margin-top: 10px;
            font-size: 10px;
            color: #64748b;
            word-break: break-all;
        }
    </style>
</head>
<body>
    @php
        $statusValue = strtolower((string) $warranty->status->value);
        $statusClass = str_contains($statusValue, 'active')
            ? 'status-active'
            : (str_contains($statusValue, 'pending') || str_contains($statusValue, 'review') || str_contains($statusValue, 'submitted')
                ? 'status-pending'
                : '');
    @endphp

    <div class="page">
        <div class="card">
            <div class="accent"></div>
            <div class="hero">
                <div class="eyebrow">Official Document · K-Elec</div>
                <h1>Warranty Certificate</h1>
                <div class="subtitle">
                    Issued for {{ $warranty->customer->full_name }} · {{ $warranty->status->label() }}
                </div>
            </div>

            <div class="content">
                <div class="ref-wrap">
                    <div class="ref-label">Warranty Reference</div>
                    <div class="ref-value">{{ $warranty->reference }}</div>
                    <span class="status-pill {{ $statusClass }}">{{ $warranty->status->label() }}</span>
                </div>

                <table class="summary">
                    <tr>
                        <td><div class="label">Customer</div><div class="value">{{ $warranty->customer->full_name }}</div></td>
                        <td><div class="label">Product</div><div class="value">{{ $warranty->displayProductName() }}</div></td>
                    </tr>
                    <tr>
                        <td><div class="label">Model</div><div class="value">{{ $warranty->displayModel() ?? '—' }}</div></td>
                        <td><div class="label">Serial Number</div><div class="value">{{ $warranty->serial_number }}</div></td>
                    </tr>
                    <tr>
                        <td><div class="label">Purchase Source</div><div class="value">{{ $warranty->purchaseSource?->name ?? $warranty->branch_name ?? '—' }}</div></td>
                        <td><div class="label">Purchase Date</div><div class="value">{{ optional($warranty->purchase_date)->format('d M Y') ?? '—' }}</div></td>
                    </tr>
                    <tr>
                        <td><div class="label">Warranty Start</div><div class="value">{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }}</div></td>
                        <td><div class="label">Warranty Expiry</div><div class="value">{{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? 'Pending verification' }}</div></td>
                    </tr>
                </table>

                <div class="support">
                    Support: {{ support_phone() }} · Keep this certificate and reference for service or claims.
                </div>

                <div class="foot">
                    Secure lookup: {{ route('warranty.lookup', ['reference' => $warranty->reference]) }}<br>
                    Warranty terms: {{ route('warranty-terms') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
