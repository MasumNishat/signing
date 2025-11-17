<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecipientController extends Controller
{
    /**
     * Display a listing of recipients (contacts).
     */
    public function index()
    {
        $accountId = auth()->user()->account_id ?? 1;
        return view('recipients.index', compact('accountId'));
    }

    /**
     * Show the form for creating a new recipient.
     */
    public function create()
    {
        $accountId = auth()->user()->account_id ?? 1;
        return view('recipients.create', compact('accountId'));
    }

    /**
     * Show the form for editing a recipient.
     */
    public function edit($id)
    {
        $accountId = auth()->user()->account_id ?? 1;
        return view('recipients.edit', [
            'recipientId' => $id,
            'accountId' => $accountId
        ]);
    }
}
