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
        'hec-lums-institution-identity' => [
            'source' => [
                'name' => 'HEC — Lahore University of Management Sciences',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-University-of-Management-Sciences.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Lahore University of Management Sciences',
                'official_name' => 'Lahore University of Management Sciences',
                'short_name' => 'LUMS',
                'slug' => 'lahore-university-of-management-sciences',
                'institution_type' => 'private university',
                'identity_terms' => ['Lahore University of Management Sciences', 'LUMS'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-punjab-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of the Punjab',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-the-Punjab.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of the Punjab',
                'official_name' => 'University of the Punjab',
                'short_name' => 'PU',
                'slug' => 'university-of-the-punjab',
                'institution_type' => 'public university',
                'identity_terms' => ['University of the Punjab'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-uet-lahore-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Engineering & Technology',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Engineering-and-Technology.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Engineering & Technology, Lahore',
                'official_name' => 'University of Engineering & Technology',
                'short_name' => 'UET Lahore',
                'slug' => 'university-of-engineering-and-technology-lahore',
                'institution_type' => 'public university',
                'identity_terms' => ['University of Engineering & Technology', 'University of Engineering and Technology'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-peshawar-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Peshawar',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/KPK/University-of-Peshawar.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Peshawar',
                'official_name' => 'University of Peshawar',
                'short_name' => 'UoP',
                'slug' => 'university-of-peshawar',
                'institution_type' => 'public university',
                'identity_terms' => ['University of Peshawar'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-ned-institution-identity' => [
            'source' => [
                'name' => 'HEC — NED University of Engineering & Technology',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Sindh/NED-University-of-Engineering--Technology.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'NED University of Engineering & Technology',
                'official_name' => 'NED University of Engineering & Technology',
                'short_name' => 'NED',
                'slug' => 'ned-university-of-engineering-and-technology',
                'institution_type' => 'public university',
                'identity_terms' => ['NED University of Engineering & Technology', 'NED University of Engineering and Technology'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-qau-institution-identity' => [
            'source' => [
                'name' => 'HEC — Quaid-i-Azam University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Islamabad/Quaid-i-Azam-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Quaid-i-Azam University',
                'official_name' => 'Quaid-i-Azam University',
                'short_name' => 'QAU',
                'slug' => 'quaid-i-azam-university',
                'institution_type' => 'public university',
                'identity_terms' => ['Quaid-i-Azam University', 'Quaid-e-Azam University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-nust-institution-identity' => [
            'source' => [
                'name' => 'HEC — National University of Sciences & Technology',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Islamabad/National-University-of-Sciences--Technology.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'National University of Sciences & Technology',
                'official_name' => 'National University of Sciences & Technology',
                'short_name' => 'NUST',
                'slug' => 'national-university-of-sciences-and-technology',
                'institution_type' => 'public university',
                'identity_terms' => ['National University of Sciences & Technology', 'National University of Sciences and Technology'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-ucp-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Central Punjab',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Central-Punjab.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Central Punjab',
                'official_name' => 'University of Central Punjab',
                'short_name' => 'UCP',
                'slug' => 'university-of-central-punjab',
                'institution_type' => 'private university',
                'identity_terms' => ['University of Central Punjab'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-gcuf-institution-identity' => [
            'source' => [
                'name' => 'HEC — Government College University, Faisalabad',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Government College University, Faisalabad',
                'official_name' => 'Government College University, Faisalabad',
                'short_name' => 'GCUF',
                'slug' => 'government-college-university-faisalabad',
                'institution_type' => 'public university',
                'identity_terms' => ['Government College University, Faisalabad', 'Government College University Faisalabad'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-air-institution-identity' => [
            'source' => [
                'name' => 'HEC — Air University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Islamabad/Air-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Air University, Islamabad',
                'official_name' => 'Air University',
                'short_name' => 'Air University',
                'slug' => 'air-university-islamabad',
                'institution_type' => 'public university',
                'identity_terms' => ['Air University', 'Air University, Islamabad'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-lcwu-institution-identity' => [
            'source' => [
                'name' => 'HEC — Lahore College for Women University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-College-for-Women-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Lahore College for Women University',
                'official_name' => 'Lahore College for Women University',
                'short_name' => 'LCWU',
                'slug' => 'lahore-college-for-women-university',
                'institution_type' => 'public university',
                'identity_terms' => ['Lahore College for Women University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-iiui-institution-identity' => [
            'source' => [
                'name' => 'HEC — International Islamic University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Islamabad/International-Islamic-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'International Islamic University',
                'official_name' => 'International Islamic University',
                'short_name' => 'IIUI',
                'slug' => 'international-islamic-university-islamabad',
                'institution_type' => 'public university',
                'identity_terms' => ['International Islamic University', 'International Islamic University Islamabad'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-awkum-institution-identity' => [
            'source' => [
                'name' => 'HEC — Abdul Wali Khan University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/KPK/Abdul-Wali-Khan-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Abdul Wali Khan University',
                'official_name' => 'Abdul Wali Khan University',
                'short_name' => 'AWKUM',
                'slug' => 'abdul-wali-khan-university',
                'institution_type' => 'public university',
                'identity_terms' => ['Abdul Wali Khan University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-gom-institution-identity' => [
            'source' => [
                'name' => 'HEC — Gomal University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/KPK/Gomal-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Gomal University',
                'official_name' => 'Gomal University',
                'short_name' => 'GU',
                'slug' => 'gomal-university',
                'institution_type' => 'public university',
                'identity_terms' => ['Gomal University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-bzu-institution-identity' => [
            'source' => [
                'name' => 'HEC — Bahauddin Zakariya University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Bahauddin-Zakariya-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Bahauddin Zakariya University',
                'official_name' => 'Bahauddin Zakariya University',
                'short_name' => 'BZU',
                'slug' => 'bahauddin-zakariya-university',
                'institution_type' => 'public university',
                'identity_terms' => ['Bahauddin Zakariya University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-sindh-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Sindh',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Sindh/University-of-Sindh.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Sindh',
                'official_name' => 'University of Sindh',
                'short_name' => 'SU',
                'slug' => 'university-of-sindh',
                'institution_type' => 'public university',
                'identity_terms' => ['University of Sindh'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-balochistan-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Balochistan',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Balochistan/University-of-Balochistan.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Balochistan',
                'official_name' => 'University of Balochistan',
                'short_name' => 'UOB',
                'slug' => 'university-of-balochistan',
                'institution_type' => 'public university',
                'identity_terms' => ['University of Balochistan'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-buitems-institution-identity' => [
            'source' => [
                'name' => 'HEC — Balochistan University of Information Technology, Engineering & Management Sciences',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Balochistan/Balochistan-University-of-Information-Technology--Management-Sciences.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Balochistan University of Information Technology, Engineering & Management Sciences',
                'official_name' => 'Balochistan University of Information Technology, Engineering & Management Sciences',
                'short_name' => 'BUITEMS',
                'slug' => 'balochistan-university-of-information-technology-engineering-management-sciences',
                'institution_type' => 'public university',
                'identity_terms' => ['Balochistan University of Information Technology, Engineering & Management Sciences', 'BUITEMS'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-uol-institution-identity' => [
            'source' => [
                'name' => 'HEC — University of Lahore',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Lahore.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'University of Lahore',
                'official_name' => 'University of Lahore',
                'short_name' => 'UOL',
                'slug' => 'university-of-lahore',
                'institution_type' => 'private university',
                'identity_terms' => ['University of Lahore'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
        'hec-lgu-institution-identity' => [
            'source' => [
                'name' => 'HEC — Lahore Garrison University',
                'type' => 'official',
                'url' => 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-Garrison-University.aspx',
                'authority_scope' => 'HEC recognized-university identity and sector listing',
            ],
            'entity_type' => 'institution',
            'institution_identity' => [
                'name' => 'Lahore Garrison University',
                'official_name' => 'Lahore Garrison University',
                'short_name' => 'LGU',
                'slug' => 'lahore-garrison-university',
                'institution_type' => 'private university',
                'identity_terms' => ['Lahore Garrison University'],
            ],
            // Qualified for isolated fixture runs only. Live retrieval and raw HTTP
            // status/content-type must be measured before any controlled pilot activation.
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
