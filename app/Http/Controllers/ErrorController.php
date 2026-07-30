<?php

namespace App\Http\Controllers;

class ErrorController extends Controller
{
    public function notFound()
    {
        return response()->view('errors.404', ['pageTitle' => 'Página no encontrada'], 404);
    }
}
