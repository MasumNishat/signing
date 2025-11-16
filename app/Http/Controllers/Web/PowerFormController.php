<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class PowerFormController extends Controller
{
    /**
     * Display a listing of PowerForms
     */
    public function index()
    {
        return view('powerforms.index');
    }

    /**
     * Show the form for creating a new PowerForm
     */
    public function create()
    {
        return view('powerforms.create');
    }

    /**
     * Display the specified PowerForm
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('powerforms.show', ['powerformId' => $id]);
    }

    /**
     * Display PowerForm submissions
     *
     * @param string $id
     */
    public function submissions($id)
    {
        return view('powerforms.submissions', ['powerformId' => $id]);
    }
}
