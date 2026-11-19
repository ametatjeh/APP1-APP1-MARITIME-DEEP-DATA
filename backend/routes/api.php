<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CorporateController;
use App\Http\Controllers\Api\v1\CountryController;
use App\Http\Controllers\Api\v1\MaritimeJurisdictionController;
use App\Http\Controllers\Api\v1\VesselController;
use App\Http\Controllers\Api\v1\VesselIdentityController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Authentication Endpoint
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated Sanctum Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Current User Profile & Logout
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 1. Country Management
        Route::prefix('countries')->group(function () {
            Route::get('/', [CountryController::class, 'index']);
            Route::get('/{id}', [CountryController::class, 'show']);
            Route::get('/{id}/regulations', [CountryController::class, 'regulations']);
            Route::get('/{id}/datasets', [CountryController::class, 'datasets']);
            
            Route::middleware(EnsureRole::class . ':super_admin,research_director,senior_investigator')->group(function () {
                Route::post('/', [CountryController::class, 'store']);
                Route::put('/{id}', [CountryController::class, 'update']);
            });
        });

        // 2. Maritime Jurisdiction (PostGIS)
        Route::prefix('jurisdictions')->group(function () {
            Route::get('/', [MaritimeJurisdictionController::class, 'index']);
            Route::get('/point-lookup', [MaritimeJurisdictionController::class, 'pointLookup']);
            Route::get('/export-geojson', [MaritimeJurisdictionController::class, 'exportGeoJson']);
            Route::get('/overlapping-claims', [MaritimeJurisdictionController::class, 'overlappingClaims']);
            Route::get('/boundary-claims', [MaritimeJurisdictionController::class, 'boundaryClaims']);
            Route::get('/{id}', [MaritimeJurisdictionController::class, 'show']);
        });

        // 3. Vessel Master Database
        Route::prefix('vessels')->group(function () {
            Route::get('/', [VesselController::class, 'index']);
            Route::get('/{id}', [VesselController::class, 'show']);
            Route::get('/{id}/timeline', [VesselController::class, 'timeline']);

            Route::middleware(EnsureRole::class . ':super_admin,research_director,senior_investigator,investigator,research_analyst')->group(function () {
                Route::post('/', [VesselController::class, 'store']);
                Route::put('/{id}', [VesselController::class, 'update']);
            });

            Route::middleware(EnsureRole::class . ':super_admin,research_director')->group(function () {
                Route::delete('/{id}', [VesselController::class, 'destroy']);
            });
        });

        // 4. Vessel Identity Intelligence
        Route::prefix('identity')->group(function () {
            Route::get('/conflicts', [VesselIdentityController::class, 'conflicts']);
            Route::get('/conflicts/{id}', [VesselIdentityController::class, 'conflictDetail']);
            Route::post('/match-candidates', [VesselIdentityController::class, 'matchCandidates']);

            Route::middleware(EnsureRole::class . ':super_admin,research_director,senior_investigator')->group(function () {
                Route::post('/conflicts/{id}/resolve', [VesselIdentityController::class, 'resolveConflict']);
            });
        });

        // 5. Corporate Intelligence
        Route::prefix('companies')->group(function () {
            Route::get('/', [CorporateController::class, 'index']);
            Route::get('/network-graph', [CorporateController::class, 'networkGraph']);
            Route::get('/{id}', [CorporateController::class, 'show']);

            Route::middleware(EnsureRole::class . ':super_admin,research_director,senior_investigator,investigator,research_analyst')->group(function () {
                Route::post('/', [CorporateController::class, 'store']);
                Route::put('/{id}', [CorporateController::class, 'update']);
            });

            Route::middleware(EnsureRole::class . ':super_admin,research_director')->group(function () {
                Route::delete('/{id}', [CorporateController::class, 'destroy']);
            });
        });
    });
});
