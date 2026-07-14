@extends('layouts.app')

@section('title', 'Employee Management')

@section('content')
    <div>
        <div class="mb-8">
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Employee Management</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Employee Management</h1>
                @endsection
            <p class="text-gray-600 mt-2">Manage employees and staff information</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
            <div class="text-center py-12">
                <i class="fas fa-user-tie text-gray-300 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Employee Management Module</h2>
                <p class="text-gray-600">This module will handle employee information and management.</p>
            </div>
        </div>
    </div>
@endsection
