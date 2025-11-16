<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        return view('billing.index');
    }

    public function plans()
    {
        return view('billing.plans');
    }

    public function invoices()
    {
        return view('billing.invoices');
    }

    public function payments()
    {
        return view('billing.payments');
    }
}
