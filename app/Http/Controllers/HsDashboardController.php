<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HsDashboardController extends Controller
{
    public function index(): View
    {
        return view('hs.index');
    }
}
