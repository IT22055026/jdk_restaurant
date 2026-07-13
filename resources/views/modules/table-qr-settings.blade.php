@extends('layouts.app')

@section('title', 'Table QR Code Settings')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="fas fa-qrcode text-red-600"></i>Table QR Codes
                </h1>
                <p class="text-gray-600 mt-2">Generate and download QR codes for each table</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('pos.qr.print') }}" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                    <i class="fas fa-print"></i> Print All
                </a>
                <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border-l-4 border-blue-600 rounded-lg p-4 mb-8">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
            <div>
                <h3 class="font-semibold text-blue-900">How to Use</h3>
                <p class="text-blue-800 text-sm mt-1">
                    Click the download button for any table to get a high-quality PNG image. Print and laminate the QR codes, then place them on your tables.
                    Customers can scan with their phones to place orders directly.
                </p>
            </div>
        </div>
    </div>

    <!-- Table QR Codes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($qrCodes as $qr)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 hover:shadow-lg transition">
                <!-- QR Code Display -->
                <div class="bg-gray-100 rounded-lg p-6 mb-6 flex items-center justify-center min-h-[280px]">
                    <img src="{{ $qr['qr_image_url'] }}" alt="Table {{ $qr['table_number'] }} QR Code" style="max-width: 200px; width: 100%; height: auto;">
                </div>

                <!-- Table Info -->
                <div class="space-y-3 mb-6">
                    <div>
                        <div class="text-3xl font-900 text-gray-900">{{ $qr['table_number'] }}</div>
                        <p class="text-sm text-gray-600">{{ $qr['table_name'] ?? 'Table' }}</p>
                    </div>

                    <div class="flex gap-2">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $qr['section'] === 'vip' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            @if($qr['section'] === 'vip')
                                <i class="fas fa-crown" style="margin-right: 4px;"></i>VIP Room
                            @else
                                <i class="fas fa-utensils" style="margin-right: 4px;"></i>Main Area
                            @endif
                        </span>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                            <i class="fas fa-users" style="margin-right: 4px;"></i>{{ $qr['capacity'] }} seats
                        </span>
                    </div>

                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-link"></i>
                            <span class="text-gray-900 font-mono text-xs">
                                /table/{{ $qr['table_id'] }}/order
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <a href="{{ route('settings.table.qr.download', $qr['table_id']) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition text-sm">
                        <i class="fas fa-download"></i> Download PNG
                    </a>
                    <a href="{{ $qr['qr_url'] }}" target="_blank"
                       class="inline-flex items-center justify-center gap-2 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-sm"
                       title="Test scanning">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>

                <!-- Test Link -->
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-800">
                    <p class="font-semibold mb-2">
                        <i class="fas fa-flask" style="margin-right: 4px;"></i>Test Link
                    </p>
                    <p class="text-yellow-700 mb-2">
                        <code class="bg-white px-2 py-1 rounded">{{ $qr['qr_url'] }}</code>
                    </p>
                    <p class="text-yellow-700">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        Open this URL on your phone to test the ordering interface
                    </p>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-600 text-lg">No tables found. Create tables first in table management.</p>
            </div>
        @endforelse
    </div>

    <!-- Additional Options -->
    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Print All Option -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg shadow-md border border-red-200 p-6">
            <div class="flex items-start gap-4 mb-4">
                <i class="fas fa-print text-red-600 text-3xl mt-1"></i>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Print All QR Codes</h3>
                    <p class="text-gray-600 text-sm">Print all table QR codes at once in a printable grid format</p>
                </div>
            </div>
            <a href="{{ route('pos.qr.print') }}" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                <i class="fas fa-print"></i> Go to Print Page
            </a>
        </div>

        <!-- API Option -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-md border border-blue-200 p-6">
            <div class="flex items-start gap-4 mb-4">
                <i class="fas fa-code text-blue-600 text-3xl mt-1"></i>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Get All QR Codes (API)</h3>
                    <p class="text-gray-600 text-sm">Programmatically fetch all QR codes in JSON format</p>
                </div>
            </div>
            <code class="text-xs text-gray-700 bg-white px-3 py-2 rounded-lg block mb-3">GET /pos/qr-codes/all</code>
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                Returns SVG QR codes for all tables
            </p>
        </div>
    </div>

    <!-- Instructions -->
    <div class="mt-12 bg-white rounded-lg shadow-md border border-gray-200 p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Quick Setup Guide</h2>

        <div class="space-y-6">
            <div class="flex gap-4">
                <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold flex-shrink-0">1</div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-2">Download QR Codes</h3>
                    <p class="text-gray-600">
                        Click the <strong>"Download PNG"</strong> button for each table to get a high-quality image.
                        Or use <strong>"Print All"</strong> to print all at once.
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold flex-shrink-0">2</div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-2">Print & Cut</h3>
                    <p class="text-gray-600">
                        Print the QR codes on sticker paper or regular paper. Cut them to size
                        (typically 5cm x 5cm or 10cm x 10cm for better scanning).
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold flex-shrink-0">3</div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-2">Laminate (Optional)</h3>
                    <p class="text-gray-600">
                        Laminate the QR codes to protect them from water and wear.
                        This helps them last longer and improves scanning reliability.
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold flex-shrink-0">4</div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-2">Place on Tables</h3>
                    <p class="text-gray-600">
                        Stick the QR codes on your tables - corner, center, or on a stand.
                        Make sure they're visible and easily scannable.
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold flex-shrink-0">5</div>
                <div>
                    <h3 class="font-bold text-gray-900 mb-2">Test & Go Live</h3>
                    <p class="text-gray-600">
                        Scan each QR code with your phone to verify it opens the menu.
                        Once verified, your customers can start ordering!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits -->
    <div class="mt-12 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg shadow-md border border-green-200 p-8">
        <h2 class="text-2xl font-bold text-green-900 mb-6">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>Benefits of QR Code Ordering
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Faster Service</h3>
                    <p class="text-green-800 text-sm">Customers order directly without waiting for staff</p>
                </div>
            </div>

            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Reduced Errors</h3>
                    <p class="text-green-800 text-sm">Direct input eliminates order taking mistakes</p>
                </div>
            </div>

            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Lower Labor Costs</h3>
                    <p class="text-green-800 text-sm">Less staff needed for order taking</p>
                </div>
            </div>

            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Better Ordering</h3>
                    <p class="text-green-800 text-sm">Customers see prices and images before ordering</p>
                </div>
            </div>

            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Instant to Kitchen</h3>
                    <p class="text-green-800 text-sm">Orders appear immediately in the kitchen display</p>
                </div>
            </div>

            <div class="flex gap-3">
                <i class="fas fa-check text-green-600 text-xl mt-1 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-green-900">Mobile Friendly</h3>
                    <p class="text-green-800 text-sm">Works on all phones - no app installation needed</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
