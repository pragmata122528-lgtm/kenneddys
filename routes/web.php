<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/seed', function () {
    Artisan::call('db:seed', ['--force' => true]);

    return response()->json([
        'message' => 'Database seeded successfully.',
        'output' => Artisan::output(),
    ]);
});
