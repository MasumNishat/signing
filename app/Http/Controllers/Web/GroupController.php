<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    /**
     * Display a unified groups management interface
     */
    public function index()
    {
        return view('groups.index');
    }

    /**
     * Display signing groups list
     */
    public function signingGroups()
    {
        return view('groups.signing.index');
    }
}
