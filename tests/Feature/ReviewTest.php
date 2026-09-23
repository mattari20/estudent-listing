<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_domain_supports_moderation_and_reputation_context(): void
    {
        $this->assertTrue(Schema::hasTable('reviews'));
        $this->assertTrue(Schema::hasTable('review_ratings'));

        foreach ([
            'institution_id','campus_id','program_id','reviewer_type','target_type',
            'title','body','overall_rating','experience_period','status',
            'verification_state','institution_response_allowed','institution_response',
            'published_at','moderated_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('reviews', $column),
                "Missing reviews column: {$column}"
            );
        }
    }
}
