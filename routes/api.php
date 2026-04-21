<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectManuscriptSearchController;

Route::get('/semantic-search', [ProjectManuscriptSearchController::class, 'search']);