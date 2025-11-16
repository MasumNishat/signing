<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class DiagnosticsController extends Controller
{
    /**
     * Display request logs
     */
    public function logs()
    {
        return view('diagnostics.logs');
    }

    /**
     * Display system health dashboard
     */
    public function health()
    {
        return view('diagnostics.health');
    }
}
