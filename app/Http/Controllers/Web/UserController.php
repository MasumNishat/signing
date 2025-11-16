<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function create()
    {
        return view('users.create');
    }

    public function show($id)
    {
        return view('users.show', ['userId' => $id]);
    }

    public function edit($id)
    {
        return view('users.edit', ['userId' => $id]);
    }

    public function profile()
    {
        return view('users.profile');
    }
}
