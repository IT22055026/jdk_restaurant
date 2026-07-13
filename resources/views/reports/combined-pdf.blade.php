<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Business Report</title>
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
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #6366f1;
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
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: #f3f4f6;
            padding: 10px;
            border-left: 3px solid #6366f1;
            border-radius: 3px;
        }
        .summary-card .label {
            font-size: 10px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
            margin-top: 15px;
            margin-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        thead {
            background: #f3f4f6;
            font-weight: 600;
            color: #4b5563;
            text-transform: uppercase;
            font-size: 9px;
        }
        th {
            padding: 6px 4px;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
        }
        td {
            padding: 5px 4px;
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
            font-size: 9px;
        }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        .payment-item {
            background: #f9fafb;
            padding: 6px;
            border-radius: 3px;
            border-left: 2px solid #6366f1;
            font-size: 10px;
        }
        .no-data {
            text-align: center;
            padding: 15px;
            color: #9ca3af;
            font-style: italic;
            font-size: 10px;
        }
        .rank {
            font-weight: 700;
            font-size: 11px;
            width: 20px;
        }
        h2 {
            font-size: 16px;
            margin-top: 10px;
            margin-bottom: 10px;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <!-- PAGE 1: SUMMARY -->
    <div class="page">
        <div class="header">
            <h1>COMPLETE BUSINESS REPORT</h1>
            <p>{{ config('app.name', 'Restaurant') }} - Executive Summary</p>
        </div>

        <div class="timestamp">
            Generated: {{ $generatedAt }}
        </div>

        <h2>📊 Key Metrics</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Revenue</div>
                <div class="value">LKR {{ number_format($totalRevenue, 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Today's Sales</div>
                <div class="value">LKR {{ number_format($todaySales, 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">This Month</div>
                <div class="value">LKR {{ number_format($monthRevenue, 0) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Avg Order Value</div>
                <div class="value">LKR {{ number_format($avgOrderValue, 0) }}</div>
            </div>
        </div>

        <!-- Top Products Summary -->
        <h2>🏆 Top 10 Products by Sales</h2>
        @if($topProducts->isEmpty())
            <div class="no-data">No product sales data available</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts->take(10) as $index => $product)
                    <tr>
                        <td class="text-center"><strong>{{ $index + 1 }}</strong></td>
                        <td>{{ $product->product_name }}</td>
                        <td><span class="badge">{{ $product->category_name }}</span></td>
                        <td class="text-right">{{ number_format($product->total_qty) }}</td>
                        <td class="text-right">LKR {{ number_format($product->total_revenue, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Payment Methods -->
        <h2>💳 Payment Methods Breakdown</h2>
        @if($paymentBreakdown->isEmpty())
            <div class="no-data">No payment data available</div>
        @else
            <div class="payment-grid">
                @foreach($paymentBreakdown as $pm)
                <div class="payment-item">
                    <strong>{{ ucfirst(str_replace('_', ' ', $pm->payment_method)) }}</strong><br>
                    {{ $pm->order_count }} orders | LKR {{ number_format($pm->total_revenue, 0) }}
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- PAGE 2: RECENT SALES -->
    <div class="page">
        <div class="header">
            <h1>RECENT SALES TRANSACTIONS</h1>
            <p>Last 50 Completed Orders</p>
        </div>

        <div class="timestamp">
            Generated: {{ $generatedAt }}
        </div>

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
                        <th>Payment</th>
                        <th class="text-right">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $sale)
                    <tr>
                        <td class="mono">{{ $sale->order_number }}</td>
                        <td>{{ $sale->table?->name ?? ($sale->table?->table_number ? 'T'.$sale->table->table_number : '—') }}</td>
                        <td>{{ $sale->customer_name ?? '—' }}</td>
                        <td class="text-right">LKR {{ number_format($sale->total, 0) }}</td>
                        <td>
                            @php
                                $methods = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank', 'mixed' => 'Mixed'];
                                $method = $methods[$sale->payment_method] ?? ucfirst($sale->payment_method ?? '—');
                            @endphp
                            <span class="badge">{{ $method }}</span>
                        </td>
                        <td class="text-right">{{ $sale->created_at->format('d M H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- PAGE 3: DETAILED PRODUCTS -->
    <div class="page">
        <div class="header">
            <h1>DETAILED PRODUCT TRANSACTIONS</h1>
            <p>All Products - Complete Sales Analysis</p>
        </div>

        <div class="timestamp">
            Generated: {{ $generatedAt }}
        </div>

        @if($topProducts->isEmpty())
            <div class="no-data">No product sales data available</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 35%">Product Name</th>
                        <th style="width: 20%">Category</th>
                        <th style="width: 15%" class="text-right">Qty Sold</th>
                        <th style="width: 20%" class="text-right">Total Revenue</th>
                        <th style="width: 5%" class="text-center">Avg Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $index => $product)
                    @php
                        $avgPrice = $product->total_qty > 0 ? $product->total_revenue / $product->total_qty : 0;
                    @endphp
                    <tr>
                        <td class="text-center"><strong>{{ $index + 1 }}</strong></td>
                        <td><strong>{{ $product->product_name }}</strong></td>
                        <td><span class="badge">{{ $product->category_name }}</span></td>
                        <td class="text-right">{{ number_format($product->total_qty) }}</td>
                        <td class="text-right">LKR {{ number_format($product->total_revenue, 0) }}</td>
                        <td class="text-right">LKR {{ number_format($avgPrice, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
