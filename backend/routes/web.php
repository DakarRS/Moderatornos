<?php

use Illuminate\Support\Facades\Route;

// In production Caddy sends / to the frontend, so this only catches direct visits to the backend.
Route::redirect('/', '/admin');
