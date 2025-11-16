<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class WorkflowController extends Controller
{
    /**
     * Display the workflow builder interface
     */
    public function builder()
    {
        return view('workflow.builder');
    }
}
