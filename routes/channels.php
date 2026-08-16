<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::post('/broadcasting/auth', function (Request $request) {
    $request->headers->set('Accept', 'application/json');

    $token = $request->bearerToken() ?: $request->query('token') ?: $request->input('token');

    if (!$token || $token === 'null') {
        return response()->json(['message' => 'Token missing from all sources!'], 401);
    }

    // Force sanctum model finding
    $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    
    if (!$accessToken || !$accessToken->tokenable) {
        return response()->json(['message' => 'Invalid or Expired Token'], 401);
    }

    $user = $accessToken->tokenable;
    
    // Yahan user attach karna zaroori hai
    Auth::guard('sanctum')->setUser($user);
    $request->setUserResolver(fn() => $user);

    return Broadcast::auth($request);
})->middleware('api'); // <---- Yahan api middleware zaroor add karein

// ================= CHANNELS =================

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.logout.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    return true;
});
// Broadcast::channel('App.Models.Employee.{id}', function ($user, $id) {
//     // Check karo ki user sahi se authenticate ho raha hai ya nahi
//     return (int) $user->id === (int) $id;
// }, ['guards' => ['sanctum', 'web']]); // Ensure correct guard is applied


// 🔥 NAYA GLOBAL CHANNEL (Simple & Universal) 🔥
Broadcast::channel('global.user.{portal}.{id}', function ($user, $portal, $id) {
    return true; 
});
