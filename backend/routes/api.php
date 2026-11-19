<?php

use App\Http\Controllers\Api\v1\AiAssistantController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CorporateController;
use App\Http\Controllers\Api\v1\CountryController;
use App\Http\Controllers\Api\v1\CrewController;
use App\Http\Controllers\Api\v1\DataImportController;
use App\Http\Controllers\Api\v1\DataSourceController;
use App\Http\Controllers\Api\v1\DataValidationController;
use App\Http\Controllers\Api\v1\DatasetVersionController;
use App\Http\Controllers\Api\v1\EvidenceController;
use App\Http\Controllers\Api\v1\GisResearchMapController;
use App\Http\Controllers\Api\v1\InvestigationCaseController;
use App\Http\Controllers\Api\v1\IuuController;
use App\Http\Controllers\Api\v1\MaritimeJurisdictionController;
use App\Http\Controllers\Api\v1\NetworkGraphController;
use App\Http\Controllers\Api\v1\PersonController;
use App\Http\Controllers\Api\v1\ReportController;
use App\Http\Controllers\Api\v1\SearchController;
use App\Http\Controllers\Api\v1\VerificationController;
use App\Http\Controllers\Api\v1\VesselController;
use App\Http\Controllers\Api\v1\VesselIdentityController;
use App\Http\Controllers\Api\v1\WatchlistController;
use App\Http\Middleware\EnsureRole;
use App\Models\AuditLog;
use Illuminate\Http\Request;
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

        // 6. Investigation Management (Phase 2)
        Route::prefix('cases')->group(function () {
            Route::get('/', [InvestigationCaseController::class, 'index']);
            Route::post('/', [InvestigationCaseController::class, 'store']);
            Route::get('/{id}', [InvestigationCaseController::class, 'show']);
            Route::put('/{id}', [InvestigationCaseController::class, 'update']);
            Route::post('/{id}/assign', [InvestigationCaseController::class, 'assign']);
            Route::post('/{id}/subjects', [InvestigationCaseController::class, 'addSubject']);
            Route::post('/{id}/notes', [InvestigationCaseController::class, 'addNote']);
            Route::post('/{id}/timelines', [InvestigationCaseController::class, 'addTimeline']);
            Route::post('/{id}/status', [InvestigationCaseController::class, 'updateStatus']);
        });

        // 7. Evidence Management & Chain of Custody (Phase 2)
        Route::prefix('evidences')->group(function () {
            Route::get('/', [EvidenceController::class, 'index']);
            Route::post('/', [EvidenceController::class, 'store']);
            Route::get('/{id}', [EvidenceController::class, 'show']);
            Route::get('/{id}/download', [EvidenceController::class, 'download']);
            Route::post('/{id}/relationships', [EvidenceController::class, 'linkRelationship']);
            Route::get('/{id}/custody', [EvidenceController::class, 'custodyLog']);
        });

        // 8. Verification Workflow (Phase 2)
        Route::prefix('verification')->group(function () {
            Route::get('/queue', [VerificationController::class, 'queue']);
            Route::post('/review', [VerificationController::class, 'review']);
            Route::get('/history', [VerificationController::class, 'history']);
        });

        // 9. Data Sources & Provenance (Phase 2)
        Route::prefix('sources')->group(function () {
            Route::get('/', [DataSourceController::class, 'index']);
            Route::get('/{id}', [DataSourceController::class, 'show']);
            Route::post('/', [DataSourceController::class, 'store']);
            Route::put('/{id}', [DataSourceController::class, 'update']);
        });

        // 10. Persons Intelligence (Phase 3)
        Route::prefix('persons')->group(function () {
            Route::get('/', [PersonController::class, 'index']);
            Route::post('/', [PersonController::class, 'store']);
            Route::get('/{id}', [PersonController::class, 'show']);
            Route::put('/{id}', [PersonController::class, 'update']);
            Route::post('/{id}/aliases', [PersonController::class, 'addAlias']);
            Route::post('/{id}/corporate-ties', [PersonController::class, 'addCorporateTie']);
            Route::post('/{id}/vessel-ties', [PersonController::class, 'addVesselTie']);
        });

        // 11. Crew Intelligence & Forced Labour (Phase 3)
        Route::prefix('crew')->group(function () {
            Route::get('/agents', [CrewController::class, 'manningAgents']);
            Route::post('/agents', [CrewController::class, 'storeManningAgent']);
            Route::get('/employments', [CrewController::class, 'employments']);
            Route::post('/employments', [CrewController::class, 'storeEmployment']);
            Route::get('/labour-violations', [CrewController::class, 'labourViolations']);
            Route::post('/labour-violations', [CrewController::class, 'storeLabourViolation']);
        });

        // 12. IUU Intelligence & RFMO Sanctions (Phase 3)
        Route::prefix('iuu')->group(function () {
            Route::get('/listings', [IuuController::class, 'listings']);
            Route::post('/listings', [IuuController::class, 'storeListing']);
            Route::get('/infractions', [IuuController::class, 'infractions']);
            Route::post('/infractions', [IuuController::class, 'storeInfraction']);
        });

        // 13. GIS & Research Spatial Map (Phase 3)
        Route::prefix('gis')->group(function () {
            Route::get('/layers', [GisResearchMapController::class, 'getMapLayers']);
            Route::get('/polygons', [GisResearchMapController::class, 'polygons']);
            Route::post('/polygons', [GisResearchMapController::class, 'storePolygon']);
            Route::get('/events', [GisResearchMapController::class, 'spatialEvents']);
            Route::post('/events', [GisResearchMapController::class, 'storeSpatialEvent']);
            Route::post('/query', [GisResearchMapController::class, 'spatialQuery']);
        });

        // 14. Audit Trail
        Route::get('/audit-logs', function (Request $request) {
            $user = $request->user();
            if (!in_array($user->role->value, ['super_admin', 'research_director'])) {
                return response()->json(['message' => 'Forbidden. Audit log inspection restricted.'], 403);
            }

            $logs = AuditLog::with('user')
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 50));

            return response()->json($logs);
        });

        // 15. Data Imports & Pipelines (Phase 4)
        Route::prefix('imports')->group(function () {
            Route::get('/', [DataImportController::class, 'index']);
            Route::post('/', [DataImportController::class, 'store']);
            Route::get('/{id}', [DataImportController::class, 'show']);
            Route::get('/{id}/quarantine', [DataImportController::class, 'quarantine']);
            Route::post('/{id}/quarantine/{quarantineId}/resolve', [DataImportController::class, 'resolveQuarantine']);
        });

        // 16. Dataset Versioning & Provenance Snapshots (Phase 4)
        Route::prefix('datasets/versions')->group(function () {
            Route::get('/', [DatasetVersionController::class, 'index']);
            Route::post('/', [DatasetVersionController::class, 'store']);
            Route::get('/{id}', [DatasetVersionController::class, 'show']);
            Route::put('/{id}', [DatasetVersionController::class, 'update']);
        });

        // 17. Data Validation Engine & Pre-flight Preview (Phase 4)
        Route::prefix('validation')->group(function () {
            Route::post('/validate-identifier', [DataValidationController::class, 'validateIdentifier']);
            Route::post('/preview', [DataValidationController::class, 'preview']);
        });

        // 18. Watchlists & Automated Alerts (Phase 5)
        Route::prefix('watchlists')->group(function () {
            Route::get('/', [WatchlistController::class, 'index']);
            Route::post('/', [WatchlistController::class, 'store']);
            Route::get('/alerts', [WatchlistController::class, 'alerts']);
            Route::post('/alerts/{id}/acknowledge', [WatchlistController::class, 'acknowledgeAlert']);
            Route::post('/alerts/{id}/resolve', [WatchlistController::class, 'resolveAlert']);
            Route::get('/{id}', [WatchlistController::class, 'show']);
            Route::put('/{id}', [WatchlistController::class, 'update']);
            Route::post('/{id}/items', [WatchlistController::class, 'addItem']);
            Route::delete('/{id}/items/{itemId}', [WatchlistController::class, 'removeItem']);
        });

        // 19. Research Reports & Dossier Generation (Phase 5)
        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::post('/vessels/{vesselId}/dossier', [ReportController::class, 'generateVesselDossier']);
            Route::post('/companies/{companyId}/dossier', [ReportController::class, 'generateCorporateDossier']);
            Route::get('/{id}', [ReportController::class, 'show']);
            Route::get('/{id}/export', [ReportController::class, 'export']);
        });

        // 20. Advanced Cross-Domain Search (Phase 5)
        Route::get('/search', [SearchController::class, 'search']);

        // 21. Interactive Network Graph Canvas (Phase 5)
        Route::get('/network/graph', [NetworkGraphController::class, 'getGraph']);

        // 22. AI Research Assistant & Multi-Agent Orchestrator (Phase 6)
        Route::prefix('ai')->group(function () {
            Route::get('/agents', [AiAssistantController::class, 'agents']);
            Route::get('/conversations', [AiAssistantController::class, 'index']);
            Route::post('/conversations', [AiAssistantController::class, 'store']);
            Route::get('/conversations/{id}', [AiAssistantController::class, 'show']);
            Route::delete('/conversations/{id}', [AiAssistantController::class, 'destroy']);
            Route::post('/conversations/{id}/query', [AiAssistantController::class, 'query']);
            Route::post('/findings/{id}/review', [AiAssistantController::class, 'reviewFinding']);
            Route::post('/vector-sync', [AiAssistantController::class, 'vectorSync']);
            Route::post('/vector-search', [AiAssistantController::class, 'vectorSearch']);
        });
    });
});
