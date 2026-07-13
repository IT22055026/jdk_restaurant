<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurantName }} - Menu QR Code</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: white;
        }

        .page {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            page-break-after: always;
        }

        .container {
            text-align: center;
        }

        h1 {
            font-size: 36px;
            margin-bottom: 10px;
            color: #667eea;
        }

        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }

        .qr-container {
            background: white;
            padding: 30px;
            border: 3px solid #667eea;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .qr-image {
            width: 300px;
            height: 300px;
        }

        .instruction {
            font-size: 14px;
            color: #333;
            margin-top: 20px;
            line-height: 1.6;
        }

        .instruction strong {
            color: #667eea;
        }

        .url {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            font-family: monospace;
            word-break: break-all;
        }

        /* Additional pages for different placements */
        .placement-guide {
            page-break-before: always;
            text-align: center;
            padding: 20mm;
        }

        .placement-guide h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .placement-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
            text-align: left;
        }

        .placement-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .placement-item h3 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .placement-item p {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Main QR Code Page -->
    <div class="page">
        <div class="container">
            <h1>{{ $restaurantName }}</h1>
            <p class="subtitle">Scan to Order Online</p>

            <div class="qr-container">
                <img src="data:image/png;base64,{{ $qrImage }}" alt="Menu QR Code" class="qr-image">
            </div>

            <div class="instruction">
                <p><strong>How it works:</strong></p>
                <p style="margin-top: 10px;">
                    1. Customers point their phone camera at this QR code<br>
                    2. They tap the notification that appears<br>
                    3. They browse your menu and place their order<br>
                    4. Their order appears in your POS system
                </p>
            </div>

            <div class="url">
                Menu URL: {{ $restaurantUrl }}/menu/scan
            </div>
        </div>
    </div>

    <!-- Placement Guide Page -->
    <div class="placement-guide">
        <h2>QR Code Placement Guide</h2>

        <div class="placement-grid">
            <div class="placement-item">
                <h3>📍 On Each Table</h3>
                <p>Place a laminated or printed QR code tent on each dining table. Customers can scan while eating and add more items.</p>
            </div>

            <div class="placement-item">
                <h3>📍 At Restaurant Entrance</h3>
                <p>Display a large QR code poster near the entrance so customers can immediately start browsing before being seated.</p>
            </div>

            <div class="placement-item">
                <h3>📍 On Menu Covers</h3>
                <p>Print the QR code on physical menu covers or the first page. Make it the first thing customers see.</p>
            </div>

            <div class="placement-item">
                <h3>📍 With Bills/Receipts</h3>
                <p>Include the QR code on order receipts and bills to encourage repeat orders and online ordering for future visits.</p>
            </div>

            <div class="placement-item">
                <h3>📍 On Window Displays</h3>
                <p>Place large format QR codes on storefront windows to attract foot traffic and enable customers to pre-browse your menu.</p>
            </div>

            <div class="placement-item">
                <h3>📍 Promotional Materials</h3>
                <p>Include the QR code in social media posts, flyers, emails, and other marketing materials to drive online orders.</p>
            </div>
        </div>
    </div>

    <!-- Size Reference Page -->
    <div class="placement-guide">
        <h2>Printing Guide & Sizes</h2>

        <div style="text-align: left; margin-top: 30px;">
            <p style="margin-bottom: 20px; font-size: 14px; line-height: 1.8;">
                <strong>Recommended QR Code Sizes:</strong><br><br>
                • <strong>Table Tents:</strong> 3" × 3" (75mm × 75mm)<br>
                • <strong>Menu Covers:</strong> 2" × 2" (50mm × 50mm)<br>
                • <strong>Window Posters:</strong> 8" × 8" (200mm × 200mm)<br>
                • <strong>Receipts:</strong> 1" × 1" (25mm × 25mm)<br><br>
                <strong>Print Settings:</strong><br>
                • Use high-quality printer (laser or inkjet)<br>
                • Print on white background for best scanning<br>
                • Ensure QR code contrast is clear and distinct<br>
                • Use lamination or protective covering for durability<br>
                • Test QR code before final printing
            </p>

            <p style="margin-top: 30px; font-size: 12px; color: #999;">
                ✓ All sizes are printable from the provided QR code image<br>
                ✓ Quality remains excellent at any size above 1" × 1"<br>
                ✓ Generated on: {{ date('Y-m-d H:i:s') }}<br>
                ✓ Menu URL: {{ $restaurantUrl }}/menu/scan
            </p>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
