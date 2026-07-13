@extends('layouts.app')

@section('title', 'Employee Management')

@section('content')
    <div>
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Employee Management</h1>
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
