<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\CaseCategory;
use App\Enums\CasePriority;
use App\Enums\CaseRole;
use App\Enums\CaseStatus;
use App\Enums\SubjectRole;
use App\Http\Controllers\Controller;
use App\Models\InvestigationCase;
use App\Policies\InvestigationCasePolicy;
use App\Services\InvestigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class InvestigationCaseController extends Controller
{
    public function __construct(
        protected InvestigationService $investigationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $policy = new InvestigationCasePolicy();
        if (!$policy->viewAny($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = InvestigationCase::with(['leadInvestigator'])
            ->withCount(['assignments', 'subjects', 'notes', 'timelines', 'evidences']);

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        $cases = $query->orderByDesc('created_at')->paginate($request->input('per_page', 25));

        return response()->json($cases);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::with([
            'leadInvestigator',
            'activeAssignments.user',
            'notes.user',
            'timelines.sourceRecord',
            'subjects',
            'evidences.collectedByUser',
        ])->findOrFail($id);

        $policy = new InvestigationCasePolicy();
        if (!$policy->view($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden. You are not assigned to this confidential case.'], 403);
        }

        return response()->json(['data' => $case]);
    }

    public function store(Request $request): JsonResponse
    {
        $policy = new InvestigationCasePolicy();
        if (!$policy->create($request->user())) {
            return response()->json(['message' => 'Forbidden. Insufficient rank to create cases.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'category' => ['required', new Enum(CaseCategory::class)],
            'priority' => ['nullable', new Enum(CasePriority::class)],
            'status' => ['nullable', new Enum(CaseStatus::class)],
            'lead_investigator_id' => 'nullable|exists:users,id',
            'incident_date_from' => 'nullable|date',
            'incident_date_to' => 'nullable|date|after_or_equal:incident_date_from',
            'tags' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $case = $this->investigationService->createCase($validated, $request->user());

        return response()->json([
            'data' => $case,
            'message' => "Investigation case {$case->case_number} registered successfully.",
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->update($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden. Not authorized to modify this case.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'nullable|string',
            'category' => ['sometimes', new Enum(CaseCategory::class)],
            'priority' => ['sometimes', new Enum(CasePriority::class)],
            'incident_date_from' => 'nullable|date',
            'incident_date_to' => 'nullable|date',
            'findings_summary' => 'nullable|string',
            'tags' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $case->update($validated);

        return response()->json(['data' => $case, 'message' => 'Case details updated.']);
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->assign($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden. Only lead or supervisory investigators can assign members.'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_case' => ['required', new Enum(CaseRole::class)],
        ]);

        $assignment = $this->investigationService->assignUser(
            $case,
            $validated['user_id'],
            CaseRole::from($validated['role_in_case']),
            $request->user()
        );

        return response()->json(['data' => $assignment, 'message' => 'Investigator assigned to case.']);
    }

    public function addSubject(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->update($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'subject_type' => 'required|in:vessel,company,person,location,other',
            'subject_id' => 'nullable|uuid',
            'subject_name' => 'required|string|max:255',
            'subject_role' => ['required', new Enum(SubjectRole::class)],
            'involvement_summary' => 'nullable|string',
            'date_identified' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $subject = $this->investigationService->addSubject($case, $validated);

        return response()->json(['data' => $subject, 'message' => 'Subject linked to investigation case.'], 201);
    }

    public function addNote(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->update($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'note_text' => 'required|string',
            'note_type' => 'nullable|in:hypothesis,witness_report,field_observation,analyst_assessment',
            'is_confidential' => 'boolean',
        ]);

        $note = $this->investigationService->addNote($case, $validated, $request->user());

        return response()->json(['data' => $note, 'message' => 'Investigative note recorded.'], 201);
    }

    public function addTimeline(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->update($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'event_date' => 'required|date',
            'event_title' => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'event_type' => 'nullable|string',
            'source_record_id' => 'nullable|exists:source_records,id',
        ]);

        $timeline = $this->investigationService->addTimelineEvent($case, $validated);

        return response()->json(['data' => $timeline, 'message' => 'Event added to case timeline.'], 201);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $case = InvestigationCase::findOrFail($id);
        $policy = new InvestigationCasePolicy();
        if (!$policy->update($request->user(), $case)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', new Enum(CaseStatus::class)],
            'findings_summary' => 'nullable|string',
        ]);

        $updated = $this->investigationService->updateStatus(
            $case,
            CaseStatus::from($validated['status']),
            $validated['findings_summary'] ?? null,
            $request->user()
        );

        return response()->json(['data' => $updated, 'message' => 'Case status updated.']);
    }
}
