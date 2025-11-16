<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of workspaces
     */
    public function index()
    {
        return view('workspaces.index');
    }

    /**
     * Show the form for creating a new workspace
     */
    public function create()
    {
        return view('workspaces.create');
    }

    /**
     * Display the specified workspace
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('workspaces.show', ['workspaceId' => $id]);
    }
}
