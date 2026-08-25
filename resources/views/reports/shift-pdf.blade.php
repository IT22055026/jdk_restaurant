<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shift Reconciliation — #{{ str_pad($shift->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
            background: #fff;
        }
        .page { padding: 28px 32px; }

        /* ── Header ── */
        .header {
            text-align: center;
            padding-bottom: 12px;
            margin-bottom: 14px;
            border-bottom: 2.5px solid #dc2626;
        }
        .header h1 { font-size: 20px; font-weight: 900; color: #111827; letter-spacing: 0.5px; text-transform: uppercase; }
        .header .doc-title {
            font-size: 11px; font-weight: 700; color: #6b7280;
            text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;
        }
        .header .generated { font-size: 9px; color: #9ca3af; margin-top: 3px; }

        /* ── Shift info strip ── */
        .info-strip {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .info-strip .info-row { display: table-row; }
        .info-strip .info-cell { display: table-cell; padding: 3px 8px 3px 0; font-size: 10px; }
        .info-strip .info-label { font-weight: 700; color: #6b7280; text-transform: uppercase; font-size: 9px; }
        .info-strip .info-value { color: #111827; font-weight: 600; }

        /* ── Three-column summary bar (Opening / Sales / Expected) ── */
        .summary-bar {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 18px;
        }
        .summary-bar td {
            width: 33.33%;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 12px;
            vertical-align: top;
            background: #f9fafb;
        }
        .summary-bar td.highlight-sales { background: #eff6ff; border-color: #bfdbfe; }
        .summary-bar td.highlight-expected { background: #f0fdf4; border-color: #86efac; }
        .summary-bar .bar-label {
            font-size: 8.5px; font-weight: 700; text-transform: uppercase;
            color: #6b7280; letter-spacing: 0.5px; margin-bottom: 4px;
        }
        .summary-bar .bar-label.sales-label { color: #2563eb; }
        .summary-bar .bar-label.expected-label { color: #059669; }
        .summary-bar .bar-value { font-size: 15px; font-weight: 900; color: #111827; }
        .summary-bar .bar-value.sales-value { color: #2563eb; }
        .summary-bar .bar-value.expected-value { color: #059669; }

        /* ── Section title ── */
        .section-title {
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            color: #374151; letter-spacing: 0.5px;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 4px; margin-bottom: 8px; margin-top: 16px;
        }

        /* ── Denomination table ── */
        table.denom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.denom-table thead tr th {
            background: #f3f4f6;
            padding: 5px 10px;
            text-align: left;
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            color: #374151;
            border-top: 1px solid #d1d5db;
            border-bottom: 1.5px solid #9ca3af;
        }
        table.denom-table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 11px;
        }
        table.denom-table tbody tr:nth-child(even) td { background: #f9fafb; }
        table.denom-table .text-right { text-align: right; }
        table.denom-table .text-center { text-align: center; }
        table.denom-table tfoot td {
            background: #1f2937;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            padding: 8px 10px;
        }
        table.denom-table tfoot .total-label { color: #d1d5db; }
        table.denom-table tfoot .total-value { color: #4ade80; font-size: 14px; }

        /* ── Variance box ── */
        .variance-box {
            padding: 10px 14px;
            border-radius: 4px;
            margin-top: 14px;
            margin-bottom: 12px;
            font-size: 12px;
            font-weight: 800;
        }
        .variance-balanced { background: #f0fdf4; border: 1.5px solid #86efac; color: #166534; }
        .variance-over     { background: #f0fdf4; border: 1.5px solid #86efac; color: #166534; }
        .variance-short    { background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; }
        .variance-box .v-row { display: table; width: 100%; }
        .variance-box .v-left { display: table-cell; vertical-align: middle; }
        .variance-box .v-right { display: table-cell; text-align: right; vertical-align: middle; font-size: 15px; }

        /* ── Notes box ── */
        .notes-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 9px 12px;
            font-size: 10.5px;
            color: #92400e;
            margin-bottom: 12px;
        }
        .notes-box .notes-title { font-weight: 800; margin-bottom: 3px; text-transform: uppercase; font-size: 9px; color: #b45309; }

        /* ── Signatures ── */
        .signatures-table { width: 100%; border-collapse: collapse; margin-top: 32px; }
        .signatures-table td { width: 50%; padding: 0 24px; text-align: center; }
        .sig-line {
            border-top: 1px solid #9ca3af;
            padding-top: 5px;
            font-size: 9.5px; font-weight: 700;
            color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .sig-name { font-size: 11px; font-weight: 900; color: #111827; margin-bottom: 28px; }

        /* ── Footer ── */
        .footer {
            margin-top: 22px;
            text-align: center;
            font-size: 8.5px; color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 7px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══ HEADER ══ --}}
    <div class="header">
        <h1>{{ config('app.name', 'Restaurant') }}</h1>
        <div class="doc-title">Shift Till Reconciliation Report</div>
        <div class="generated">Generated: {{ $generatedAt }}</div>
    </div>

    {{-- ══ SHIFT INFO STRIP ══ --}}
    @php
        $startTime = $shift->started_at ?? $shift->created_at;
        $endTime   = $shift->ended_at;
    @endphp
    <div class="info-strip">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="padding:3px 16px 3px 0; width:50%">
                    <div class="info-label">Cashier</div>
                    <div class="info-value" style="font-size:12px; font-weight:800; color:#111827">{{ $shift->user->name ?? 'N/A' }}</div>
                </td>
                <td style="padding:3px 0; width:50%">
                    <div class="info-label">Shift Reference</div>
                    <div class="info-value" style="font-size:12px; font-weight:800; color:#111827">#SHIFT-{{ str_pad($shift->id, 5, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:3px 16px 3px 0">
                    <div class="info-label">Started At</div>
                    <div class="info-value">{{ $startTime->format('d M Y, h:i A') }}</div>
                </td>
                <td style="padding:3px 0">
                    <div class="info-label">Closed At</div>
                    <div class="info-value">{{ $endTime ? $endTime->format('d M Y, h:i A') : 'Still Active' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ OPENING / SALES / EXPECTED SUMMARY BAR ══ --}}
    <table class="summary-bar">
        <tr>
            <td>
                <div class="bar-label">Opening Balance</div>
                <div class="bar-value">Rs. {{ number_format($shift->opening_balance ?? 0, 2) }}</div>
            </td>
            <td class="highlight-sales">
                <div class="bar-label sales-label">Sales (This Shift)</div>
                <div class="bar-value sales-value">Rs. {{ number_format($totalSales, 2) }}</div>
            </td>
            <td class="highlight-expected">
                <div class="bar-label expected-label">Expected in Till</div>
                <div class="bar-value expected-value">Rs. {{ number_format($shift->expected_total ?? ($shift->opening_balance + $totalSales), 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- ══ COUNT THE TILL — DENOMINATION BREAKDOWN ══ --}}
    <div class="section-title">Count the Till (Notes)</div>
    <table class="denom-table">
        <thead>
            <tr>
                <th>Denomination</th>
                <th class="text-center">Notes Count</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $allDenominations = [5000, 1000, 500, 100, 50, 20];
                $denomMap = collect($denominations)->keyBy('denomination');
                $hasAny = false;
            @endphp
            @foreach($allDenominations as $dval)
                @php
                    $row = $denomMap->get($dval);
                    $qty = $row ? $row->quantity : 0;
                    $sub = $row ? $row->subtotal : 0;
                    if ($qty > 0) $hasAny = true;
                @endphp
                <tr>
                    <td style="font-weight:700">Rs. {{ number_format($dval) }}</td>
                    <td class="text-center" style="color: {{ $qty > 0 ? '#111827' : '#d1d5db' }}; font-weight: {{ $qty > 0 ? '700' : '400' }}">
                        {{ $qty > 0 ? $qty : '—' }}
                    </td>
                    <td class="text-right" style="font-weight:700; color: {{ $qty > 0 ? '#111827' : '#d1d5db' }}">
                        {{ $qty > 0 ? 'Rs. ' . number_format($sub, 2) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="total-label" style="font-weight:700">TOTAL COUNTED</td>
                <td class="text-center" style="color:#d1d5db; font-size:10px">
                    {{ collect($denominations)->sum('quantity') }} notes
                </td>
                <td class="text-right total-value">Rs. {{ number_format($shift->actual_total ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ══ VARIANCE BOX ══ --}}
    @php
        $variance = $shift->variance ?? 0;
    @endphp
    @if($variance == 0)
        <div class="variance-box variance-balanced">
            <div class="v-row">
                <div class="v-left">&#10003;&nbsp; RECONCILIATION: PERFECTLY BALANCED</div>
                <div class="v-right">Rs. 0.00</div>
            </div>
        </div>
    @elseif($variance > 0)
        <div class="variance-box variance-over">
            <div class="v-row">
                <div class="v-left">&#9650;&nbsp; OVERAGE — Till has MORE than expected</div>
                <div class="v-right">+Rs. {{ number_format($variance, 2) }}</div>
            </div>
        </div>
    @else
        <div class="variance-box variance-short">
            <div class="v-row">
                <div class="v-left">&#9660;&nbsp; SHORTAGE — Till is SHORT of expected</div>
                <div class="v-right">-Rs. {{ number_format(abs($variance), 2) }}</div>
            </div>
        </div>
    @endif

    {{-- ══ NOTES (OPTIONAL) ══ --}}
    @if(!empty($shift->notes))
        <div class="notes-box">
            <div class="notes-title">Notes / Discrepancies</div>
            {{ $shift->notes }}
        </div>
    @endif

    {{-- ══ SIGNATURES ══ --}}
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-name">{{ $shift->user->name ?? 'Cashier' }}</div>
                <div class="sig-line">Cashier Signature</div>
            </td>
            <td>
                <div style="height: 28px;"></div>
                <div class="sig-line">Manager / Supervisor Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ config('app.name', 'Restaurant') }} POS &bull; Shift Till Reconciliation &bull; Generated {{ $generatedAt }}
    </div>

</div>
</body>
</html>
