<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyReviewAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_cannot_access_geography_review_actions(): void
    {
        $row = $this->geographyCandidateRow();

        $this->post(route('institutes.operations.geography-resolve', $row))
            ->assertRedirect();
    }

    public function test_authenticated_viewer_cannot_submit_geography_review_action(): void
    {
        $row = $this->geographyCandidateRow();
        $viewer = User::create([
            'name' => 'Geography Viewer',
            'email' => 'geography-viewer@example.test',
            'password' => 'test-password',
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->post(route('institutes.operations.geography-resolve', $row))
            ->assertRedirect(route('institutes.login'));
    }

    private function geographyCandidateRow(): ImportRow
    {
        $batch = ImportBatch::create([
            'batch_key' => 'geo-access-' . uniqid(),
            'entity_type' => 'geography',
            'source_type' => 'test',
            'status' => 'validated',
            'received_at' => now(),
        ]);

        return ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Test City', 'slug' => 'test-city', 'type' => 'city'],
            'normalized_payload' => ['name' => 'Test City', 'slug' => 'test-city', 'type' => 'city'],
        ]);
    }
}
