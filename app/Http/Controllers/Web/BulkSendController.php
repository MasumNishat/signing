<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class BulkSendController extends Controller
{
    /**
     * Display a listing of bulk send batches
     */
    public function index()
    {
        return view('bulk.index');
    }

    /**
     * Show the form for creating a new bulk send batch
     */
    public function create()
    {
        return view('bulk.create');
    }

    /**
     * Display the specified bulk send batch
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('bulk.show', ['batchId' => $id]);
    }
}
