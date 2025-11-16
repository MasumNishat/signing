<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class FolderController extends Controller
{
    /**
     * Display a listing of folders
     */
    public function index()
    {
        return view('folders.index');
    }

    /**
     * Show the form for creating a new folder
     */
    public function create()
    {
        return view('folders.create');
    }
}
