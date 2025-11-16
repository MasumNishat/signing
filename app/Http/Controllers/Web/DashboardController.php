<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Show the dashboard widgets configuration page
     */
    public function widgets()
    {
        return view('dashboard.widgets');
    }

    /**
     * Show the activity feed page
     */
    public function activity()
    {
        return view('dashboard.activity');
    }
}
