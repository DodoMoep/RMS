<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function switch($locale)
    {
        if (!in_array($locale, ['en', 'de'])) {
            abort(400);
        }

        Session::put('locale', $locale);
        
        return redirect()->back();
    }
}
