<?php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => 'BLOOSAT BSS API', 'version' => '3.0', 'status' => 'running']);
});
