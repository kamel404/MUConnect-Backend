<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
header('Accept: application/json');

class TestController extends Controller
{
    
    public function testPost(Request $request)
    {
        dd("POST request received!");
        return response()->json([
            'message' => 'POST request successful!',
            'data' => $request->all(),
        ], 200);
    }
}
