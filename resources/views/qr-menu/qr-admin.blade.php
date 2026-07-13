@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white p-8">
            <h1 class="text-3xl font-bold mb-2">QR Code Generator</h1>
            <p class="text-purple-100">Generate QR codes to share your digital menu with customers</p>
        </div>

        <div class="p-8 space-y-8">
            <!-- QR Code Display -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <h2 class="text-2xl font-bold text-gray-800">Your Menu QR Code</h2>
                    <p class="text-gray-600">Customers can scan this QR code to view your menu and place orders online.</p>

                    <div class="bg-gray-100 p-8 rounded-lg flex items-center justify-center">
                        <img id="qrCode" src="{{ route('qr.generate') }}" alt="Menu QR Code" class="w-64 h-64 object-contain">
                    </div>

                    <div class="text-sm text-gray-600">
                        <p><strong>QR Code URL:</strong></p>
                        <p class="text-purple-600 font-mono break-all">{{ config('app.url') }}/menu/scan</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-2xl font-bold text-gray-800">Download Options</h2>

                    <a href="{{ route('qr.download') }}" class="flex items-center gap-3 w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="fas fa-download"></i>
                        Download as PNG
                    </a>

                    <a href="{{ route('qr.pdf') }}" class="flex items-center gap-3 w-full bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                        <i class="fas fa-file-pdf"></i>
                        Print Ready PDF
                    </a>

                    <button id="copyUrlBtn" class="flex items-center gap-3 w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                        <i class="fas fa-copy"></i>
                        Copy Menu URL
                    </button>

                    <!-- Usage Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6 space-y-3">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                            How to Use
                        </h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li>✓ Download the QR code and print it</li>
                            <li>✓ Place it on your table, menu, or storefront</li>
                            <li>✓ Customers scan with any smartphone camera</li>
                            <li>✓ They see your full menu and can place orders</li>
                            <li>✓ Orders appear in your POS system automatically</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Instructions for placement -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 space-y-3">
                <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <i class="fas fa-info-circle text-yellow-600"></i>
                    Recommended Placement
                </h3>
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                    <li><strong>Table Tents:</strong> Place QR code on all dining tables</li>
                    <li><strong>Entry Point:</strong> Display at restaurant entrance</li>
                    <li><strong>Menus:</strong> Print on physical menu covers or pages</li>
                    <li><strong>Receipts:</strong> Add to order receipts for repeat orders</li>
                    <li><strong>Signage:</strong> Place on window displays and storefront</li>
                    <li><strong>Social Media:</strong> Share the menu URL on social platforms</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('copyUrlBtn').addEventListener('click', function() {
        const url = '{{ config("app.url") }}/menu/scan';
        navigator.clipboard.writeText(url).then(() => {
            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.classList.add('bg-green-700');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('bg-green-700');
            }, 2000);
        });
    });
</script>
@endsection
