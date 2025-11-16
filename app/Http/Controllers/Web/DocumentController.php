<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents
     */
    public function index()
    {
        return view('documents.index');
    }

    /**
     * Show the upload interface
     */
    public function upload()
    {
        return view('documents.upload');
    }

    /**
     * Show the document viewer
     *
     * @param string $id
     */
    public function viewer($id)
    {
        return view('documents.viewer', ['documentId' => $id]);
    }
}
