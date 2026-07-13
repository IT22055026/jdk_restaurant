<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.4;
        }
        .page {
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
        }
        .timestamp {
            text-align: right;
            font-size: 10px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: #f9fafb;
            padding: 12px;
            border-left: 4px solid #dc2626;
            border-radius: 3px;
        }
        .summary-card .label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .summary-card .value {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        thead {
            background: #f3f4f6;
            font-weight: 600;
            color: #4b5563;
            text-transform: uppercase;
            font-size: 10px;
        }
        th {
            padding: 8px 5px;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
        }
        td {
            padding: 7px 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .mono {
            font-family: 'Courier New', monospace;
            font-size: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-cash {
            background: #dcfce7;
            color: #166534;
        }
        .badge-card {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-bank {
            background: #e9d5ff;
            color: #7e22ce;
        }
        .badge-mixed {
            background: #fed7aa;
            color: #9a3412;
        }
        .payment-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .payment-item {
            background: #f9fafb;
            padding: 8px;
            border-radius: 3px;
            border-left: 3px solid #dc2626;
        }
        .payment-item .method {
            font-weight: 600;
            color: #1f2937;
            font-size: 11px;
        }
        .payment-item .count {
            color: #6b7280;
            font-size: 10px;
        }
        .payment-item .amount {
            font-weight: 700;
            color: #1f2937;
            margin-top: 2px;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>SALES REPORT</h1>
            <p>{{ config('app.name', 'Restaurant') }} - Detailed Sales Analysis</p>
        </div>

        <div class="timestamp">
            Generated: {{ $generatedAt }}
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Revenue</div>
                <div class="value">LKR {{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Today's Sales</div>
                <div class="value">LKR {{ number_format($todaySales, 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">This Month</div>
                <div class="value">LKR {{ number_format($monthRevenue, 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Avg Order Value</div>
                <div class="value">LKR {{ number_format($avgOrderValue, 2) }}</div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="section-title">📋 Recent Sales (Last 100 Orders)</div>

        @if($recentSales->isEmpty())
            <div class="no-data">No sales data available</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Customer</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Payment</th>
                        <th class="text-right">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $sale)
                    <tr>
                        <td class="mono">{{ $sale->order_number }}</td>
                        <td>{{ $sale->table?->name ?? ($sale->table?->table_number ? 'T'.$sale->table->table_number : '—') }}</td>
                        <td>{{ $sale->customer_name ?? '—' }}</td>
                        <td class="text-right">LKR {{ number_format($sale->total, 2) }}</td>
                        <td class="text-center">
                            @php
                                $pmMap = [
                                    'cash' => 'badge-cash',
                                    'card' => 'badge-card',
                                    'bank_transfer' => 'badge-bank',
                                    'mixed' => 'badge-mixed',
                                ];
                                $badgeClass = $pmMap[$sale->payment_method] ?? '';
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—')) }}
                            </span>
                        </td>
                        <td class="text-right">{{ $sale->created_at->format('d M, H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Payment Methods Breakdown -->
        <div class="section-title">💳 Payment Methods Breakdown</div>

        @if($paymentBreakdown->isEmpty())
            <div class="no-data">No payment data available</div>
        @else
            <div class="payment-section">
                @foreach($paymentBreakdown as $pm)
                <div class="payment-item">
                    <div class="method">{{ ucfirst(str_replace('_', ' ', $pm->payment_method)) }}</div>
                    <div class="count">{{ $pm->order_count }} order{{ $pm->order_count != 1 ? 's' : '' }}</div>
                    <div class="amount">LKR {{ number_format($pm->total_revenue, 2) }}</div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
