<?php

use Illuminate\Support\Facades\Route;


Route::middleware(['auth'])->group(function () {

    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class);
    Route::resource('expertises', \App\Http\Controllers\ExpertiseController::class);
    Route::resource('engineers', \App\Http\Controllers\EngineerController::class);
    Route::resource('co-ordinators', \App\Http\Controllers\CoordinatorController::class);
    Route::resource('technicians', \App\Http\Controllers\TechnicianController::class);
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::resource('jobs', \App\Http\Controllers\JobController::class);
    Route::resource('requisitions', \App\Http\Controllers\RequisitionController::class);
    
    Route::post('category-list', [\App\Helpers\Helper::class, 'getCategories'])->name('category-list');
    Route::post('product-list', [\App\Helpers\Helper::class, 'getProducts'])->name('product-list');
    Route::post('country-list', [\App\Helpers\Helper::class, 'getCountries'])->name('country-list');
    Route::post('state-list', [\App\Helpers\Helper::class, 'getStatesByCountry'])->name('state-list');
    Route::post('city-list', [\App\Helpers\Helper::class, 'getCitiesByState'])->name('city-list');
    Route::post('user-list', [\App\Helpers\Helper::class, 'getUsers'])->name('user-list');
    Route::post('product-category-list', [\App\Helpers\Helper::class, 'getProductCategories'])->name('product-category-list');
    Route::post('department-list', [\App\Helpers\Helper::class, 'getDepartments'])->name('department-list');
    Route::post('expertise-list', [\App\Helpers\Helper::class, 'getExpertise'])->name('expertise-list');
    Route::post('job-list', [\App\Helpers\Helper::class, 'getJobs'])->name('job-list');

    Route::post('products/{product}/images/upload', [\App\Http\Controllers\ProductImageController::class, 'upload'])->name('products.images.upload');
    Route::get('products/{product}/images', [\App\Http\Controllers\ProductImageController::class, 'list'])->name('products.images.list');
    Route::delete('products/{product}/images/{media}', [\App\Http\Controllers\ProductImageController::class, 'delete'])->name('products.images.delete');
    Route::post('products/{product}/images/sort', [\App\Http\Controllers\ProductImageController::class, 'sort'])->name('products.images.sort');    
    Route::get('products/{product}/images/list', [\App\Http\Controllers\ProductController::class, 'images'])->name('products-media');

    Route::get('settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
    
    Route::get('job-settings', [App\Http\Controllers\SettingController::class, 'jobIndex'])->name('job.settings');
    Route::post('job-settings-update', [App\Http\Controllers\SettingController::class, 'jobUpdate'])->name('job.settings-update');

    Route::post('jobs/{job}/reschedule', [\App\Http\Controllers\JobController::class, 'reschedule'])->name('jobs.reschedule');
    Route::post('/jobs/{job}/change-status', [\App\Http\Controllers\JobController::class, 'changeStatus'])->name('jobs.change-status');

});