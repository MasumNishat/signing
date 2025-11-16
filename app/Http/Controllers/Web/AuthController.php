<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login page
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request (Session-based authentication)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate with session
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Generate personal access token for API calls
            $user = Auth::user();

            try {
                $token = $user->createToken('web-session')->accessToken;
                // Store token in session for frontend use
                session(['api_token' => $token]);
            } catch (\Exception $e) {
                // Log error but don't block login
                \Log::warning('Failed to create API token on login', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);

                // Set a flag that API token is unavailable
                session(['api_token_error' => true]);
            }

            return redirect()->intended('dashboard');
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Show the registration page
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Get or create default account
        $account = \App\Models\Account::first();
        if (!$account) {
            $account = \App\Models\Account::create([
                'account_name' => 'Default Account',
                'plan_id' => \App\Models\Plan::first()?->id ?? 1,
            ]);
        }

        // Create user with account_id
        $user = User::create([
            'account_id' => $account->id,
            'user_name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['name'],
            'user_status' => 'active',
            'user_type' => 'individual',
        ]);

        // Auto-login after registration
        Auth::login($user);

        // Generate personal access token for API calls
        try {
            $token = $user->createToken('web-session')->accessToken;
            session(['api_token' => $token]);
        } catch (\Exception $e) {
            // Log error but don't block registration
            \Log::warning('Failed to create API token on registration', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            session(['api_token_error' => true]);
        }

        return redirect('dashboard');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Revoke all user tokens before logout
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Show the forgot password page
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show the reset password page
     *
     * @param string $token
     */
    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->get('email')
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
