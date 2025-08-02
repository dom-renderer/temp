<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::redirect('', 'dashboard');
Route::get('register', fn () => abort(404));