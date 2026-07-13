@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Customer Management</h1>
            <p class="text-gray-600 mt-2">Manage customers and customer information</p>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
            <div class="text-center py-12">
                <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Customer Management Module</h2>
                <p class="text-gray-600">This module will handle customer information, registration, and management.</p>
            </div>
        </div>
    </div>
@endsection
