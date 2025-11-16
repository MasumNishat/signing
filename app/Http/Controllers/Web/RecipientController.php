<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecipientController extends Controller
{
    public function index()
    {
        return view('recipients.index');
    }

    public function create()
    {
        return view('recipients.create');
    }

    public function edit($id)
    {
        return view('recipients.edit', ['recipientId' => $id]);
    }
}
