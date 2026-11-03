<?php

namespace App\Services;

use App\Models\ChangeEvent;
use App\Models\SourceChangeAssertionLink;

class SourceChangeEventProjector
{
    public function project(SourceChangeAssertionLink $link): ChangeEvent
    {
        $assertion = $link->assertion;

        return ChangeEvent::query()->firstOrCreate(
            ['source_change_assertion_link_id' => $link->id],
            [
                'subject_type' => $assertion->subject_type,
                'subject_id' => $assertion->subject_id,
                'event_type' => 'source_content_changed',
                'materiality' => $link->impact,
                'actor_type' => 'system',
                'status' => 'detected',
                'summary' => 'A monitored source changed and may affect attribute: '.($link->attribute ?? 'unknown').'.',
                'detected_at' => $link->created_at,
            ]
        );
    }
}
