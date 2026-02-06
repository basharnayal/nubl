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


//     // التحقق من دور
// if (auth()->user()->hasRole('admin')) {
//     // ...
// }

// // التحقق من عدة أدوار
// if (auth()->user()->hasAnyRole(['admin', 'donor'])) {
//     // ...
// }

// // التحقق من جميع الأدوار
// if (auth()->user()->hasAllRoles(['admin', 'donor'])) {
//     // ...
// }


}