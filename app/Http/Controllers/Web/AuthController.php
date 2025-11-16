<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
     * Show the registration page
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Show the forgot password page
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
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
}
