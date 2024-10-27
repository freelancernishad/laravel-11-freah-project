<?php

namespace App\Http\Controllers\Api\Auth\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function me(Request $request)
    {
        return response()->json(['token' => 'your_jwt_token']);
    }
}
