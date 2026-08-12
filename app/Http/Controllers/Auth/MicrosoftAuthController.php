<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    /**
     * Redirect the user to Microsoft's OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('microsoft')
            ->stateless()
            ->scopes(['openid', 'profile', 'email', 'User.Read'])
            ->redirect();
    }

    /**
     * Handle Microsoft OAuth callback.
     * - If email not in local DB → deny access with flash error.
     * - If found → log in and redirect based on role.
     */
    public function callback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Microsoft Auth Callback Exception: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Microsoft authentication failed: ' . $e->getMessage());
        }

        $email = $microsoftUser->getEmail();

        if (! $email) {
            return redirect()->route('login')
                ->with('error', 'Access Denied: Could not retrieve email from your Microsoft account.');
        }

        // DB Check Enforcement: Only allow pre-registered users
        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Access Denied: Your Microsoft account is not registered in the system portal.');
        }

        // Update Azure info on first Microsoft login
        if (! $user->azure_id) {
            $user->update([
                'azure_id'      => $microsoftUser->getId(),
                'auth_provider' => 'microsoft',
            ]);
        }

        // Authenticate user
        Auth::login($user, true);

        // Redirect based on role
        return redirect()->to($user->getPostLoginRedirect());
    }
}
