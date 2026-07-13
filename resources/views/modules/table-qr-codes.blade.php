<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Table QR Codes — Restaurant BYOB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .qr-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .qr-code-container {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 280px;
        }

        .qr-code-svg {
            max-width: 250px;
            width: 100%;
            height: auto;
        }

        .table-info {
            margin-top: 12px;
        }

        .table-number {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            margin: 8px 0;
        }

        .table-name {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0;
        }

        .table-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            margin: 4px 2px;
        }

        .badge-vip {
            background: #7c3aed;
            color: #fff;
        }

        .badge-main {
            background: #3b82f6;
            color: #fff;
        }

        @media print {
            body { background: #fff; }
            .print-header { display: none; }
            .print-footer { display: none; }
            .qr-card { border: 1px solid #d1d5db; page-break-inside: avoid; break-inside: avoid; }
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .grid-4 { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 768px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 480px) {
            .grid-4 { grid-template-columns: repeat(1, 1fr); }
        }

        .btn-primary {
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.15s;
        }

        .btn-primary:hover { background: #b91c1c; }

        .btn-secondary {
            background: #f1f5f9;
            color: #374151;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.15s;
        }

        .btn-secondary:hover { background: #e2e8f0; }
    </style>
    @include('layouts.dark-mode')
</head>
<body>

@include('layouts.navbar')

<div style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <div class="print-header" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0 0 4px;">
                    <i class="fas fa-qrcode" style="color: #3b82f6; margin-right: 8px;"></i>Table QR Codes
                </h1>
                <p style="font-size: 14px; color: #64748b; margin: 0;">Print and place on your restaurant tables</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button onclick="window.print()" class="btn-primary">
                    <i class="fas fa-print" style="margin-right: 6px;"></i>Print All
                </button>
                <a href="{{ route('pos.index') }}" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>Back to POS
                </a>
            </div>
        </div>

        <div style="background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px;">
            <p style="font-size: 13px; color: #166534; margin: 0;">
                <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
                <strong>{{ count($qrCodes) }} table QR codes ready to print.</strong> Customers scan with their phones to place orders directly from their table!
            </p>
        </div>
    </div>

    <div class="grid-4">
        @foreach($qrCodes as $qr)
            <div class="qr-card">
                <div class="qr-code-container">
                    {!! $qr['qr_svg'] !!}
                </div>
                <div class="table-info">
                    <div class="table-number">{{ $qr['table_number'] }}</div>
                    <div class="table-name">{{ $qr['table_name'] }}</div>
                    <div>
                        <span class="table-badge {{ $qr['section'] === 'vip' ? 'badge-vip' : 'badge-main' }}">
                            {{ $qr['section'] === 'vip' ? '🟣 VIP' : '🍽 Main' }}
                        </span>
                        <span class="table-badge" style="background: #e5e7eb; color: #374151;">
                            <i class="fas fa-users" style="margin-right: 3px;"></i>{{ $qr['capacity'] }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="print-footer" style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 12px;">
        <p>
            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
            Customers can scan these QR codes with their phones to place orders from their table. Generated on {{ now()->format('M d, Y H:i') }}
        </p>
    </div>
</div>

</body>
</html>
