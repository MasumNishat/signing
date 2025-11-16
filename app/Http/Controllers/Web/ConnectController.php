<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ConnectController extends Controller
{
    /**
     * Display a listing of webhooks
     */
    public function index()
    {
        return view('connect.index');
    }

    /**
     * Show the form for creating a new webhook
     */
    public function create()
    {
        return view('connect.create');
    }

    /**
     * Display the specified webhook
     *
     * @param string $id
     */
    public function show($id)
    {
        return view('connect.show', ['webhookId' => $id]);
    }

    /**
     * Display webhook delivery logs
     *
     * @param string $id
     */
    public function logs($id)
    {
        return view('connect.logs', ['webhookId' => $id]);
    }

    /**
     * Display webhook testing interface
     *
     * @param string $id
     */
    public function test($id)
    {
        return view('connect.test', ['webhookId' => $id]);
    }
}
