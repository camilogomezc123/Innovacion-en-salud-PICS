<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $patient = Patient::query()->where('email', $credentials['email'])->first();

        if ($patient && $patient->password && Hash::check($credentials['password'], $patient->password)) {
            $request->session()->regenerate();
            Auth::guard('patient')->login($patient);

            return redirect()->intended(route('portal.home'));
        }

        $caregiver = Caregiver::query()->where('email', $credentials['email'])->where('is_active', true)->first();

        if ($caregiver && Hash::check($credentials['password'], $caregiver->password)) {
            $request->session()->regenerate();
            Auth::guard('caregiver')->login($caregiver);

            return redirect()->intended(route('portal.home'));
        }

        return back()->withErrors(['email' => 'El correo o la contraseña no son correctos.'])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();
        Auth::guard('caregiver')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
