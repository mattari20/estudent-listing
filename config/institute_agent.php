<?php

return [
    'profiles' => [
        // Paused until a standalone COMSATS parent institution is present in canonical data.
        // The currently known COMSATS record is campus-level and must not be used as the parent.
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
                'enabled' => false,
                'auto_discovery' => false,
                'use_ai' => false,
                'cadence' => 'weekly',
                'fresh_after_days' => 7,
                'stale_after_days' => 30,
                'priority' => 'normal',
            ],
        ],
        // Initial, source-corroborated identity candidate. This profile does not require
        // a canonical parent and never promotes/publishes the resulting import row.
        'gcu-lahore-institution-identity' => [
            'source' => [
                'name' => 'Higher Education Commission — Government College University, Lahore',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx',
                'authority_scope' => 'official HEC university identity, recognition status, sector, and Lahore location',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Government College University Lahore',
                'official_name' => 'Government College University, Lahore',
                'short_name' => 'GCU',
                'slug' => 'government-college-university-lahore',
                'institution_type' => 'public university',
                'identity_terms' => [
                    'Government College University, Lahore',
                    'Government College University Lahore',
                ],
            ],
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
        // Safe pilot profile: the parent is an existing standalone canonical institution,
        // and the source is the university's current official BS admissions page.
        'gcu-lahore-programs' => [
            'institution_slug' => 'government-college-university-lahore',
            'source' => [
                'name' => 'Government College University Lahore — Official BS Programs',
                'type' => 'official',
                'url' => 'https://gcu.edu.pk/admissions.php?pg=bs-program',
                'authority_scope' => 'official undergraduate admissions/program listing',
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
        // Paused until a standalone canonical parent institution is resolved.
        'cui-lahore-campus' => [
            'institution_slug' => 'comsats-university-islamabad-lahore-campus',
            'source' => [
                'name' => 'COMSATS University Islamabad — Lahore Campus',
                'type' => 'official',
                'url' => 'https://lahore.comsats.edu.pk/',
                'authority_scope' => 'official institutional campus information',
            ],
            'entity_type' => 'campus',
            'monitoring' => [
                'enabled' => false,
                'auto_discovery' => false,
                'use_ai' => false,
                'cadence' => 'weekly',
                'fresh_after_days' => 7,
                'stale_after_days' => 30,
                'priority' => 'normal',
            ],
        ],
    ],
];
