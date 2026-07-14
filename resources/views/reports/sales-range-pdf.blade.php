<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.4; }
        .page { padding: 20px; background: white; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #dc2626; padding-bottom: 12px; }
        .header h1 { font-size: 26px; color: #1f2937; margin-bottom: 4px; }
        .header p { color: #6b7280; font-size: 12px; }
        .meta { display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; margin-bottom: 16px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .summary-card { background: #f9fafb; padding: 10px 14px; border-left: 4px solid #dc2626; border-radius: 3px; }
        .summary-card .label { font-size: 10px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .summary-card .value { font-size: 15px; font-weight: 700; color: #1f2937; }
        .payment-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .payment-pill { background: #f3f4f6; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 600; color: #374151; border: 1px solid #e5e7eb; }
        .payment-pill span { font-weight: 400; color: #6b7280; }
        .section-title { font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 8px; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead { background: #f3f4f6; }
        th { padding: 7px 5px; text-align: left; border-bottom: 2px solid #d1d5db; font-size: 9px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mono { font-family: 'Courier New', monospace; font-size: 9px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 2px; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .badge-cash { background: #dcfce7; color: #166534; }
        .badge-card { background: #dbeafe; color: #1e40af; }
        .badge-bank { background: #e9d5ff; color: #7e22ce; }
        .badge-mixed { background: #fed7aa; color: #9a3412; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .no-data { text-align: center; padding: 20px; color: #9ca3af; font-style: italic; }
        .total-row td { font-weight: 700; background: #fef2f2; border-top: 2px solid #dc2626; }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <h1>SALES REPORT</h1>
        <p>{{ config('app.name', 'Restaurant') }} &mdash; {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p>
    </div>

    <div class="meta">
        <span>Period: <strong>{{ $from->format('d M Y') }}</strong> &rarr; <strong>{{ $to->format('d M Y') }}</strong></span>
        <span>Generated: {{ $generatedAt }}</span>
    </div>

    <!-- Summary -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Period Revenue</div>
            <div class="value">LKR {{ number_format($rangeRevenue, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Orders</div>
            <div class="value">{{ number_format($rangeCount) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Avg Order Value</div>
            <div class="value">LKR {{ number_format($rangeAvg, 2) }}</div>
        </div>
    </div>

    <!-- Payment breakdown -->
    @if($rangePayments->count())
    <div class="section-title">Payment Methods</div>
    <div class="payment-row">
        @foreach($rangePayments as $rp)
        <div class="payment-pill">
            {{ ucfirst(str_replace('_', ' ', $rp->payment_method)) }}
            <span>&mdash; {{ $rp->order_count }} orders &mdash; LKR {{ number_format($rp->total_revenue, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Orders table -->
    <div class="section-title">Orders ({{ $rangeCount }} total)</div>

    @if($sales->isEmpty())
        <div class="no-data">No sales found for this date range.</div>
    @else
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Token</th>
                <th>Customer</th>
                <th class="text-center">Payment</th>
                <th class="text-right">Total</th>
                <th class="text-right">Date &amp; Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            @php
                $pmMap = ['cash'=>'badge-cash','card'=>'badge-card','bank_transfer'=>'badge-bank','mixed'=>'badge-mixed'];
                $bc = $pmMap[$sale->payment_method] ?? '';
            @endphp
            <tr>
                <td class="mono">{{ $sale->order_number }}</td>
                <td>{{ $sale->token_number ? '#' . str_pad($sale->token_number, 2, '0', STR_PAD_LEFT) : '—' }}</td>
                <td>{{ $sale->customer_name ?? '—' }}</td>
                <td class="text-center">
                    <span class="badge {{ $bc }}">{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—')) }}</span>
                </td>
                <td class="text-right">LKR {{ number_format($sale->total, 2) }}</td>
                <td class="text-right">{{ $sale->created_at->format('d M Y, H:i') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td class="text-right">LKR {{ number_format($rangeRevenue, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        {{ config('app.name', 'Restaurant') }} &bull; Sales Report &bull; {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} &bull; Generated {{ $generatedAt }}
    </div>

</div>
</body>
</html>
