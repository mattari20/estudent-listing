<?php

/*
|--------------------------------------------------------------------------
| National institute source registry (discovery inventory, not approval)
|--------------------------------------------------------------------------
|
| This is a source strategy catalog, not a claim that every directory has
| been crawled or every listed institution verified. Entries stay
| needs_live_validation until retrieval, parsing, freshness, and jurisdiction
| coverage have been measured. No entry enables automatic canonical writes.
|
*/

return [
    'jurisdictions' => [
        'punjab' => ['label' => 'Punjab', 'kind' => 'province'],
        'sindh' => ['label' => 'Sindh', 'kind' => 'province'],
        'khyber_pakhtunkhwa' => ['label' => 'Khyber Pakhtunkhwa', 'kind' => 'province'],
        'balochistan' => ['label' => 'Balochistan', 'kind' => 'province'],
        'islamabad_capital_territory' => ['label' => 'Islamabad Capital Territory', 'kind' => 'territory'],
        'gilgit_baltistan' => ['label' => 'Gilgit-Baltistan', 'kind' => 'territory'],
        'azad_jammu_and_kashmir' => ['label' => 'Azad Jammu and Kashmir', 'kind' => 'territory'],
    ],

    'institution_categories' => [
        'public_university',
        'private_university',
        'degree_awarding_institution',
        'constituent_college',
        'affiliated_college',
        'independent_college',
        'school',
        'technical_institute',
        'vocational_institute',
        'dae_provider',
        'professional_institute',
        'campus',
        'sub_campus',
        'department',
        'program',
    ],

    'sources' => [
        'hec_recognized_campuses' => [
            'authority' => 'Higher Education Commission Pakistan',
            'source_url' => 'https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx',
            'authority_directory_url' => 'https://www.hec.gov.pk/english/services/faculty/nrpu/Pages/default.aspx',
            'source_kind' => 'official_regulator_directory',
            'categories' => ['public_university', 'private_university', 'degree_awarding_institution', 'campus', 'sub_campus'],
            'jurisdictions' => ['punjab', 'sindh', 'khyber_pakhtunkhwa', 'balochistan', 'islamabad_capital_territory', 'gilgit_baltistan', 'azad_jammu_and_kashmir'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'navttc_partner_institutes' => [
            'authority' => 'National Vocational and Technical Training Commission',
            'source_url' => 'https://navttc.gov.pk/partner-institutes/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_national_tvet_directory',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['punjab', 'sindh', 'khyber_pakhtunkhwa', 'balochistan', 'islamabad_capital_territory'],
            'validation_status' => 'needs_live_validation',
            'coverage_note' => 'The retrieved directory visibly lists five regions; AJK and GB coverage must be separately verified.',
            'activation' => 'manual_review_required',
        ],
        'ibcc_board_directory' => [
            'authority' => 'Inter Boards Coordination Commission',
            'source_url' => 'https://ibcc.edu.pk/ibcc-forum-members/',
            'authority_directory_url' => 'https://ibcc.edu.pk/qualification-awarding-bodies/',
            'source_kind' => 'official_board_directory',
            'source_role' => 'authority_discovery_only',
            'categories' => ['school', 'independent_college'],
            'jurisdictions' => ['punjab', 'sindh', 'khyber_pakhtunkhwa', 'balochistan', 'islamabad_capital_territory', 'gilgit_baltistan', 'azad_jammu_and_kashmir'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'hec_recognized_affiliated_colleges' => [
            'authority' => 'Higher Education Commission Pakistan',
            'source_url' => 'https://www.hec.gov.pk/Site/Accreditation/default.aspx',
            'authority_directory_url' => 'https://www.hec.gov.pk/english/services/faculty/nrpu/Pages/default.aspx',
            'source_kind' => 'official_affiliation_policy_and_registry_directory',
            'categories' => ['affiliated_college', 'constituent_college'],
            'jurisdictions' => ['punjab', 'sindh', 'khyber_pakhtunkhwa', 'balochistan', 'islamabad_capital_territory', 'gilgit_baltistan', 'azad_jammu_and_kashmir'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'ibcc_qualification_awarding_bodies' => [
            'authority' => 'Inter Boards Coordination Commission',
            'source_url' => 'https://ibcc.edu.pk/qualification-awarding-bodies/',
            'source_kind' => 'official_qualification_awarding_body_registry',
            'source_role' => 'authority_and_recognition_discovery',
            'categories' => ['school', 'independent_college'],
            'jurisdictions' => ['punjab', 'sindh', 'khyber_pakhtunkhwa', 'balochistan', 'islamabad_capital_territory', 'gilgit_baltistan', 'azad_jammu_and_kashmir'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'federal_education_authorities' => [
            'authority' => 'Ministry of Federal Education and Professional Training',
            'source_url' => 'https://www.mofept.gov.pk/Detail/NWNmYmVjMmQtODM1Yy00Y2RjLWE1YjEtYjYwZjVkODBlYWU4',
            'source_kind' => 'official_federal_authority_directory',
            'categories' => ['school', 'independent_college', 'professional_institute'],
            'jurisdictions' => ['islamabad_capital_territory'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'punjab_tevta' => [
            'authority' => 'Technical Education and Vocational Training Authority Punjab',
            'source_url' => 'https://tevta.gop.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_provincial_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['punjab'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'sindh_tevta' => [
            'authority' => 'Sindh Technical Education and Vocational Training Authority',
            'source_url' => 'https://stevta.gos.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_provincial_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['sindh'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'khyber_pakhtunkhwa_tevta' => [
            'authority' => 'Khyber Pakhtunkhwa Technical Education and Vocational Training Authority',
            'source_url' => 'https://kptevta.gov.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_provincial_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['khyber_pakhtunkhwa'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'balochistan_tevta' => [
            'authority' => 'Balochistan Technical Education and Vocational Training Authority',
            'source_url' => 'https://btevta.gob.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_provincial_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['balochistan'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'ajk_tevta' => [
            'authority' => 'Azad Jammu and Kashmir Technical Education and Vocational Training Authority',
            'source_url' => 'https://tevta.gok.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_regional_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['azad_jammu_and_kashmir'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
        'gilgit_baltistan_tevta' => [
            'authority' => 'Gilgit-Baltistan Technical Education and Vocational Training Authority',
            'source_url' => 'https://gbtevta.gov.pk/',
            'authority_directory_url' => 'https://navttc.gov.pk/tvet-sector/',
            'source_kind' => 'official_regional_tvet_authority',
            'categories' => ['technical_institute', 'vocational_institute', 'dae_provider'],
            'jurisdictions' => ['gilgit_baltistan'],
            'validation_status' => 'needs_live_validation',
            'activation' => 'manual_review_required',
        ],
    ],

    'coverage_gaps' => [
        'school_level_directory_by_province' => 'not_yet_validated',
        'provincial_college_directories' => 'not_yet_validated',
        'professional_regulator_directories' => 'not_yet_catalogued',
        'national_source_retrieval_and_parser_health' => 'not_yet_measured',
        'deduplication_and_identity_conflict_rates' => 'not_yet_measured',
        'candidate_and_review_package_counts' => 'not_yet_measured',
        'ajk_and_gb_tvet_partner_coverage' => 'not_yet_verified',
    ],

    'safety' => [
        'automatic_canonical_creation' => false,
        'automatic_approval' => false,
        'automatic_publication' => false,
        'http_403_means_nonexistent' => false,
        'unverified_source_profiles_enabled' => false,
        'identity_conflicts_require_private_review' => true,
    ],
];
