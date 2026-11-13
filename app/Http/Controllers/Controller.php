<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    // Laravel 10: trait ini yang menyediakan $this->authorize() dan $this->validate().
    use AuthorizesRequests, ValidatesRequests;
}
