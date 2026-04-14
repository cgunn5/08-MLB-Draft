<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WorkingBoardController extends Controller
{
    public function index(): View
    {
        return view('board.index');
    }
}
