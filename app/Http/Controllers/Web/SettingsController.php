<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function account()
    {
        return view('settings.account');
    }

    public function notifications()
    {
        return view('settings.notifications');
    }

    public function security()
    {
        return view('settings.security');
    }

    public function branding()
    {
        return view('settings.branding');
    }
}
