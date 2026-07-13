<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Report</title>
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
            border-bottom: 3px solid #f59e0b;
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
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: #fffbeb;
            padding: 12px;
            border-left: 4px solid #f59e0b;
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
        .rank {
            font-weight: 700;
            font-size: 13px;
            color: #f59e0b;
            width: 25px;
        }
        .rank.gold {
            color: #fbbf24;
        }
        .rank.silver {
            color: #d1d5db;
        }
        .rank.bronze {
            color: #ea580c;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: 600;
            background: #f0fdf4;
            color: #166534;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-style: italic;
        }
        .bar {
            display: inline-block;
            height: 3px;
            background: #f59e0b;
            border-radius: 1px;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>PRODUCT TRANSACTIONS REPORT</h1>
            <p>{{ config('app.name', 'Restaurant') }} - Product Sales Analysis</p>
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
                <div class="label">Total Products Listed</div>
                <div class="value">{{ number_format($topProducts->count()) }}</div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="section-title">🏆 Top Selling Products - Detailed Transactions</div>

        @if($topProducts->isEmpty())
            <div class="no-data">No product sales data available</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 30%">Product Name</th>
                        <th style="width: 20%">Category</th>
                        <th style="width: 15%" class="text-right">Quantity Sold</th>
                        <th style="width: 20%" class="text-right">Total Revenue</th>
                        <th style="width: 10%" class="text-center">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $maxQty = $topProducts->max('total_qty') ?? 1;
                    @endphp
                    @foreach($topProducts as $index => $product)
                    <tr>
                        <td class="text-center">
                            @if($index === 0)
                                <span class="rank gold">🥇</span>
                            @elseif($index === 1)
                                <span class="rank silver">🥈</span>
                            @elseif($index === 2)
                                <span class="rank bronze">🥉</span>
                            @else
                                <span class="rank">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $product->product_name }}</strong></td>
                        <td><span class="badge">{{ $product->category_name }}</span></td>
                        <td class="text-right"><strong>{{ number_format($product->total_qty) }}</strong></td>
                        <td class="text-right"><strong>LKR {{ number_format($product->total_revenue, 2) }}</strong></td>
                        <td class="text-center">
                            @php
                                $percentage = ($product->total_qty / $maxQty) * 100;
                            @endphp
                            <div style="width: {{ $percentage }}%; height: 3px; background: #f59e0b; border-radius: 1px; display: inline-block; min-width: 10px;"></div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Summary Statistics -->
        <div class="section-title">📊 Summary Statistics</div>

        @if($topProducts->isNotEmpty())
            @php
                $totalQty = $topProducts->sum('total_qty');
                $totalRev = $topProducts->sum('total_revenue');
                $avgQty = $totalQty / $topProducts->count();
                $avgRev = $totalRev / $topProducts->count();
            @endphp
            <table style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th class="text-right">Value</th>
                        <th class="text-right">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Quantity Sold</strong></td>
                        <td class="text-right">{{ number_format($totalQty) }} units</td>
                        <td class="text-right">Across all products</td>
                    </tr>
                    <tr>
                        <td><strong>Total Products Revenue</strong></td>
                        <td class="text-right">LKR {{ number_format($totalRev, 2) }}</td>
                        <td class="text-right">From listed products</td>
                    </tr>
                    <tr>
                        <td><strong>Average Quantity Per Product</strong></td>
                        <td class="text-right">{{ number_format($avgQty, 0) }} units</td>
                        <td class="text-right">Per product average</td>
                    </tr>
                    <tr>
                        <td><strong>Average Revenue Per Product</strong></td>
                        <td class="text-right">LKR {{ number_format($avgRev, 2) }}</td>
                        <td class="text-right">Per product average</td>
                    </tr>
                    <tr>
                        <td><strong>Top Product</strong></td>
                        <td class="text-right">{{ number_format($topProducts->first()->total_qty) }} units</td>
                        <td class="text-right">{{ $topProducts->first()->product_name }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
