<?php

namespace App\Services;

use App\Enums\CasePriority;
use App\Enums\CaseRole;
use App\Enums\CaseStatus;
use App\Models\AuditLog;
use App\Models\CaseAssignment;
use App\Models\CaseNote;
use App\Models\CaseSubject;
use App\Models\CaseTimeline;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvestigationService
{
    /**
     * Create an investigation case with automatic case number generation and lead assignment.
     */
    public function createCase(array $data, User $creator): InvestigationCase
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['case_number'] = InvestigationCase::generateCaseNumber();
            $data['lead_investigator_id'] = $data['lead_investigator_id'] ?? $creator->id;
            $data['status'] = $data['status'] ?? CaseStatus::DRAFT;
            $data['priority'] = $data['priority'] ?? CasePriority::MEDIUM;

            $case = InvestigationCase::create($data);

            // Automatically create lead assignment
            CaseAssignment::create([
                'case_id' => $case->id,
                'user_id' => $case->lead_investigator_id,
                'role_in_case' => CaseRole::LEAD,
                'assigned_at' => now(),
                'is_active' => true,
                'assigned_by_user_id' => $creator->id,
            ]);

            AuditLog::log('create_case', $case, null, [
                'case_number' => $case->case_number,
                'title' => $case->title,
                'lead_investigator_id' => $case->lead_investigator_id,
            ]);

            return $case->load(['leadInvestigator', 'assignments.user']);
        });
    }

    /**
     * Assign an investigator or analyst to a case.
     */
    public function assignUser(InvestigationCase $case, string $userId, CaseRole $role, User $assigner): CaseAssignment
    {
        return DB::transaction(function () use ($case, $userId, $role, $assigner) {
            $assignment = CaseAssignment::updateOrCreate(
                [
                    'case_id' => $case->id,
                    'user_id' => $userId,
                    'role_in_case' => $role,
                ],
                [
                    'assigned_at' => now(),
                    'unassigned_at' => null,
                    'is_active' => true,
                    'assigned_by_user_id' => $assigner->id,
                ]
            );

            AuditLog::log('assign_case_user', $case, null, [
                'assigned_user_id' => $userId,
                'role' => $role->value,
                'assigned_by' => $assigner->id,
            ]);

            return $assignment->load('user');
        });
    }

    /**
     * Link a subject (vessel, company, person, location) to a case.
     */
    public function addSubject(InvestigationCase $case, array $subjectData): CaseSubject
    {
        return DB::transaction(function () use ($case, $subjectData) {
            $subjectData['case_id'] = $case->id;
            $subject = CaseSubject::create($subjectData);

            AuditLog::log('add_case_subject', $case, null, [
                'subject_type' => $subject->subject_type,
                'subject_id' => $subject->subject_id,
                'subject_name' => $subject->subject_name,
                'role' => $subject->subject_role->value,
            ]);

            return $subject;
        });
    }

    /**
     * Add an investigative note to a case.
     */
    public function addNote(InvestigationCase $case, array $noteData, User $author): CaseNote
    {
        $noteData['case_id'] = $case->id;
        $noteData['user_id'] = $author->id;
        $note = CaseNote::create($noteData);

        AuditLog::log('add_case_note', $case, null, [
            'note_id' => $note->id,
            'title' => $note->title,
            'is_confidential' => $note->is_confidential,
        ]);

        return $note->load('user');
    }

    /**
     * Record a historical event in the case timeline.
     */
    public function addTimelineEvent(InvestigationCase $case, array $timelineData): CaseTimeline
    {
        $timelineData['case_id'] = $case->id;
        $timeline = CaseTimeline::create($timelineData);

        AuditLog::log('add_case_timeline', $case, null, [
            'event_title' => $timeline->event_title,
            'event_date' => $timeline->event_date,
        ]);

        return $timeline->load('sourceRecord');
    }

    /**
     * Transition case status with analyst conclusion.
     */
    public function updateStatus(InvestigationCase $case, CaseStatus $newStatus, ?string $conclusion, User $actor): InvestigationCase
    {
        $oldStatus = $case->status->value;

        $case->update([
            'status' => $newStatus,
            'findings_summary' => $conclusion ?? $case->findings_summary,
        ]);

        AuditLog::log('update_case_status', $case, ['status' => $oldStatus], [
            'status' => $newStatus->value,
            'findings_summary' => $conclusion,
            'actor_id' => $actor->id,
        ]);

        return $case;
    }
}
