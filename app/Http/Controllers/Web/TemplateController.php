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

    /**
     * Show the form for using a template to create an envelope
     *
     * @param string $id
     */
    public function use($id)
    {
        return view('templates.use', ['templateId' => $id]);
    }

    /**
     * Show the template sharing page
     *
     * @param string $id
     */
    public function share($id)
    {
        return view('templates.share', ['templateId' => $id]);
    }

    /**
     * Show the template import page
     */
    public function import()
    {
        return view('templates.import');
    }

    /**
     * Show favorite templates
     */
    public function favorites()
    {
        return view('templates.favorites');
    }
}
