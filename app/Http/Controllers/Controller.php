<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function view(string $view, array $data = [])
    {
        return view($view, $data);
    }

    protected function redirectWithSuccess(string $route, string $message)
    {
        return redirect()->route($route)->with('success', $message);
    }

    protected function redirectWithError(string $route, string $message)
    {
        return redirect()->route($route)->with('error', $message);
    }
}
