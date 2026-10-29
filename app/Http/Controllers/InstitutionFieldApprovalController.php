<?php

namespace App\Http\Controllers;

use App\Models\InstitutionFieldApproval;
use App\Services\InstitutionFieldApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InstitutionFieldApprovalController
{
    public function store(Request $request, InstitutionFieldApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'source_id' => ['required', 'integer', 'exists:sources,id'],
            'source_change_observation_id' => ['required', 'integer', 'exists:source_change_observations,id'],
            'source_snapshot_id' => ['required', 'integer', 'exists:source_snapshots,id'],
            'evidence_id' => ['required', 'integer', 'exists:evidence,id'],
            'expected_current_value' => ['present', 'nullable', 'string'],
            'proposed_value' => ['required', 'string', 'max:50000'],
        ]);

        try {
            $proposal = $approvals->createPending(
                (int) $data['institution_id'],
                (int) $data['source_id'],
                (int) $data['source_change_observation_id'],
                (int) $data['source_snapshot_id'],
                (int) $data['evidence_id'],
                $data['expected_current_value'],
                $data['proposed_value']
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['proposal' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Institution description proposal #'.$proposal->id.' created for review.');
    }

    public function apply(InstitutionFieldApproval $proposal, OfficialSourceCanonicalRefreshService $refresh): RedirectResponse
    {
        try {
            $result = $refresh->refreshApprovedInstitutionDescription($proposal->id);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return back()->with('status', 'Institution description proposal #'.$proposal->id.' application outcome: '.$result['outcome'].'.');
    }

    public function decide(Request $request, InstitutionFieldApproval $proposal, InstitutionFieldApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $decision = $approvals->decide($proposal->id, $data['decision'], $data['reason'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Institution description proposal #'.$decision->id.' '.$decision->status.'.');
    }
}
