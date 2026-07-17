<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($validated, $request->boolean('remember'))) {
            $user = Auth::user();
            $firstModule = $user->role?->modules()->first();
            $landingRoute = ($firstModule && Route::has($firstModule->route))
                ? route($firstModule->route)
                : route('dashboard');

            // Deliberately not using redirect()->intended() here: a role's
            // landing page must be deterministic (e.g. Cashier always lands on
            // POS), not wherever the browser happened to be pointed at before
            // the session expired — which could be a page this role can't see.
            return redirect()->to($landingRoute);
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
