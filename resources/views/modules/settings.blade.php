@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div>
        <div class="mb-8">
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Settings</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Settings</h1>
                @endsection
            <p class="text-gray-600 mt-2">System and application settings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Table QR Code Settings -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg shadow-sm border border-red-200 p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-qrcode text-red-600 text-3xl"></i>
                            <h3 class="text-xl font-bold text-gray-900">Table QR Codes</h3>
                        </div>
                        <p class="text-gray-600 text-sm">Download QR codes for each table</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-700 space-y-2 mb-4">
                    <li>✓ Download QR for individual tables</li>
                    <li>✓ Print all QR codes at once</li>
                    <li>✓ Customers scan with phones</li>
                    <li>✓ Direct ordering from tables</li>
                </ul>
                <a href="{{ route('settings.table.qr') }}" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition">
                    <i class="fas fa-qrcode"></i> Table QR Codes
                </a>
            </div>

            <!-- QR Code Management -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-sm border border-purple-200 p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-qrcode text-purple-600 text-3xl"></i>
                            <h3 class="text-xl font-bold text-gray-900">QR Menu</h3>
                        </div>
                        <p class="text-gray-600 text-sm">Generate and manage menu QR codes for customers</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-700 space-y-2 mb-4">
                    <li>✓ Create scanning QR codes for your menu</li>
                    <li>✓ Download printable QR code images</li>
                    <li>✓ Generate PDF for easy printing</li>
                    <li>✓ Enable online ordering via mobile</li>
                </ul>
                <a href="{{ route('qr.admin') }}" class="inline-flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    <i class="fas fa-qrcode"></i> Manage QR Codes
                </a>
            </div>

            <!-- General Settings -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-sm border border-blue-200 p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-cog text-blue-600 text-3xl"></i>
                            <h3 class="text-xl font-bold text-gray-900">General Settings</h3>
                        </div>
                        <p class="text-gray-600 text-sm">Configure system and application settings</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-700 space-y-2 mb-4">
                    <li>✓ Restaurant information</li>
                    <li>✓ System configuration</li>
                    <li>✓ Application preferences</li>
                </ul>
                <button disabled class="inline-flex items-center gap-2 bg-gray-400 text-white px-4 py-2 rounded-lg font-semibold cursor-not-allowed">
                    <i class="fas fa-cog"></i> Coming Soon
                </button>
            </div>
        </div>
    </div>
@endsection
