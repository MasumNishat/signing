<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EnvelopeController extends Controller
{
    /**
     * Display a listing of envelopes
     */
    public function index()
    {
        return view('envelopes.index');
    }

    /**
     * Show the form for creating a new envelope
     */
    public function create()
    {
        return view('envelopes.create');
    }

    /**
     * Display the specified envelope
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('envelopes.show', ['envelopeId' => $id]);
    }

    /**
     * Show the form for editing the specified envelope
     *
     * @param string $id
     */
    public function edit($id)
    {
        return view('envelopes.edit', ['envelopeId' => $id]);
    }

    /**
     * Display advanced search interface
     */
    public function advancedSearch()
    {
        return view('envelopes.advanced-search');
    }
}
