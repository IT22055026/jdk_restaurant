<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Stock Report</title>
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
            border-bottom: 3px solid #059669;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: #ecfdf5;
            padding: 12px;
            border-left: 4px solid #059669;
            border-radius: 3px;
        }
        .summary-card.warn {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }
        .summary-card.danger {
            background: #fef2f2;
            border-left-color: #dc2626;
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
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-ok {
            background: #f0fdf4;
            color: #166534;
        }
        .badge-low {
            background: #fffbeb;
            color: #92400e;
        }
        .badge-out {
            background: #fef2f2;
            color: #991b1b;
        }
        .badge-unlimited {
            background: #eff6ff;
            color: #1e40af;
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
            <h1>CURRENT STOCK REPORT</h1>
            <p>{{ config('app.name', 'Restaurant') }} - Live Stock Levels</p>
        </div>

        <div class="timestamp">
            Generated: {{ $generatedAt }}
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Products</div>
                <div class="value">{{ number_format($totalProducts) }}</div>
            </div>
            <div class="summary-card warn">
                <div class="label">Low Stock Products</div>
                <div class="value">{{ number_format($lowStockProducts) }}</div>
            </div>
            <div class="summary-card danger">
                <div class="label">Out of Stock Products</div>
                <div class="value">{{ number_format($outOfStockProducts) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Included Items</div>
                <div class="value">{{ number_format($totalIngredients) }}</div>
            </div>
            <div class="summary-card warn">
                <div class="label">Low Stock Included Items</div>
                <div class="value">{{ number_format($lowStockIngredients) }}</div>
            </div>
            <div class="summary-card danger">
                <div class="label">Out of Stock Included Items</div>
                <div class="value">{{ number_format($outOfStockIngredients) }}</div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="section-title">📦 Products — Current Stock</div>

        @if($products->isEmpty())
            <div class="no-data">No products found</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%">Product Name</th>
                        <th style="width: 15%">Category</th>
                        <th style="width: 15%">Type</th>
                        <th style="width: 15%" class="text-right">Current Stock</th>
                        <th style="width: 15%" class="text-right">Threshold</th>
                        <th style="width: 15%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>
                            @if($product->is_unlimited_stock)
                                Unlimited
                            @elseif($product->is_finished_good)
                                Finished Good
                            @else
                                Recipe / BOM
                            @endif
                        </td>
                        <td class="text-right">
                            @if($product->is_unlimited_stock)
                                &infin;
                            @elseif($product->is_finished_good)
                                {{ number_format($product->quantity) }}
                            @elseif(!$product->hasRecipe())
                                No recipe
                            @else
                                {{ number_format($product->availableStock()) }}
                            @endif
                        </td>
                        <td class="text-right">{{ $product->low_stock_threshold ?? '—' }}</td>
                        <td class="text-center">
                            @if($product->is_unlimited_stock)
                                <span class="badge badge-unlimited">Unlimited</span>
                            @elseif($product->availableStock() === 0)
                                <span class="badge badge-out">Out of Stock</span>
                            @elseif($product->isLowStock())
                                <span class="badge badge-low">Low Stock</span>
                            @else
                                <span class="badge badge-ok">In Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Included Items Table -->
        <div class="section-title">🧂 Included Items — Current Stock</div>

        @if($ingredients->isEmpty())
            <div class="no-data">No ingredients found</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 30%">Item Name</th>
                        <th style="width: 15%">Unit</th>
                        <th style="width: 20%" class="text-right">Current Stock</th>
                        <th style="width: 20%" class="text-right">Threshold</th>
                        <th style="width: 15%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ingredients as $ingredient)
                    <tr>
                        <td><strong>{{ $ingredient->name }}</strong></td>
                        <td>{{ $ingredient->unit }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format($ingredient->quantity, 3), '0'), '.') }}</td>
                        <td class="text-right">{{ $ingredient->low_stock_threshold !== null ? rtrim(rtrim(number_format($ingredient->low_stock_threshold, 3), '0'), '.') : '—' }}</td>
                        <td class="text-center">
                            @if((float) $ingredient->quantity <= 0)
                                <span class="badge badge-out">Out of Stock</span>
                            @elseif($ingredient->isLowStock())
                                <span class="badge badge-low">Low Stock</span>
                            @else
                                <span class="badge badge-ok">In Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
