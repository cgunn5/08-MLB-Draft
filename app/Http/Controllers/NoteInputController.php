<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NoteInputController extends Controller
{
    public function index(): View
    {
        return view('notes.index');
    }
}
