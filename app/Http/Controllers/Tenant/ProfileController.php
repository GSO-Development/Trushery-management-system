<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(string $company_slug)
    {
        $company = Company::where('slug', $company_slug)->firstOrFail();
        $user    = auth()->user();

        return view('tenant.profile', compact('company', 'user'));
    }

    public function updatePassword(Request $request, string $company_slug)
    {
        $user = auth()->user();

        if ($user->auth_provider === 'microsoft') {
            return back()->withErrors(['password' => 'Password changes are managed via your Microsoft Entra ID account.']);
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password updated successfully!');
    }
}
