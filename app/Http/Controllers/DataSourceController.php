<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DataSourceController extends Controller
{
    public function index(): View
    {
        return view('data-sources.index');
    }
}
