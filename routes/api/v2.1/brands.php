<?php

use App\Http\Controllers\Api\V2_1\BrandController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Brand API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Brand management routes for the Signing API.
| Handles brand customization, logos, resources, and email content.
|
| All routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate permissions
|
*/

Route::prefix('accounts/{accountId}/brands')->name('brands.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Brand CRUD Operations
    |--------------------------------------------------------------------------
    */

    // List brands
    Route::get('/', [BrandController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('index');

    // Create brand
    Route::post('/', [BrandController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.create'])
        ->name('store');

    // Get brand
    Route::get('/{brandId}', [BrandController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('show');

    // Update brand
    Route::put('/{brandId}', [BrandController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('update');

    // Delete brand
    Route::delete('/{brandId}', [BrandController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.delete'])
        ->name('destroy');

    // Bulk delete brands
    Route::delete('/', [BrandController::class, 'destroyBulk'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.delete'])
        ->name('destroy_bulk');

    // Export brand
    Route::get('/{brandId}/file', [BrandController::class, 'exportBrand'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('export');

    /*
    |--------------------------------------------------------------------------
    | Brand Logo Management
    |--------------------------------------------------------------------------
    */

    // Upload logo
    Route::post('/{brandId}/logos', [BrandController::class, 'uploadLogo'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('logos.upload');

    // Get logo
    Route::get('/{brandId}/logos/{logoType}', [BrandController::class, 'getLogo'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('logos.show');

    // Update/Put logo
    Route::put('/{brandId}/logos/{logoType}', [BrandController::class, 'updateLogo'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('logos.update');

    // Delete logo
    Route::delete('/{brandId}/logos/{logoType}', [BrandController::class, 'deleteLogo'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('logos.destroy');

    /*
    |--------------------------------------------------------------------------
    | Brand Resource Management
    |--------------------------------------------------------------------------
    */

    // List resources (must come before specific resource routes)
    Route::get('/{brandId}/resources', [BrandController::class, 'listResources'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('resources.index');

    // Upload resource
    Route::post('/{brandId}/resources', [BrandController::class, 'uploadResource'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('resources.upload');

    // Get resource
    Route::get('/{brandId}/resources/{resourceContentType}', [BrandController::class, 'getResource'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('resources.show');

    // Update/Put resource
    Route::put('/{brandId}/resources/{resourceContentType}', [BrandController::class, 'uploadResource'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('resources.update');

    // Delete resource
    Route::delete('/{brandId}/resources/{resourceContentType}', [BrandController::class, 'deleteResource'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('resources.destroy');

    /*
    |--------------------------------------------------------------------------
    | Brand Email Content
    |--------------------------------------------------------------------------
    */

    // Get email content
    Route::get('/{brandId}/email_content', [BrandController::class, 'getEmailContent'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.view'])
        ->name('email_content.show');

    // Update email content
    Route::put('/{brandId}/email_content', [BrandController::class, 'updateEmailContent'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:brands.manage'])
        ->name('email_content.update');
});
