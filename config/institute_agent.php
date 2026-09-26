<?php

return [
    'profiles' => [
        'cui-lahore' => [
            'institution_slug' => 'comsats-university-islamabad-lahore-campus',
            'source' => [
                'name' => 'COMSATS University Islamabad — Lahore Campus Academics',
                'type' => 'official',
                'url' => 'https://lahore.comsats.edu.pk/academics.aspx',
                'authority_scope' => 'official institutional academic programme listing',
            ],
            'entity_type' => 'program',
            'monitoring' => [
                'enabled' => true,
                'auto_discovery' => true,
                'use_ai' => false,
                'cadence' => 'weekly',
                'fresh_after_days' => 7,
                'stale_after_days' => 30,
                'priority' => 'normal',
            ],
        ],
    ],
];
