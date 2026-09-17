<?php

namespace App\Http\Controllers;

class PublicController extends Controller
{
    public function homepage()
    {
        return view('welcome');
    }
}
