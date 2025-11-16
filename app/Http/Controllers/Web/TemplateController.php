<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index()
    {
        return view('templates.index');
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Display the specified template
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('templates.show', ['templateId' => $id]);
    }

    /**
     * Show the form for editing the specified template
     *
     * @param string $id
     */
    public function edit($id)
    {
        return view('templates.edit', ['templateId' => $id]);
    }
}
