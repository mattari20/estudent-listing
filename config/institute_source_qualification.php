<?php

// Read-only qualification inventory. These definitions are deliberately
// separate from institute_agent monitoring profiles and never activate them.
return [
    'hec-universities' => [
        ['id' => 'gcu-lahore', 'profile' => 'gcu-lahore-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'lums', 'profile' => 'hec-lums-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'punjab-university', 'profile' => 'hec-punjab-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'uet-lahore', 'profile' => 'hec-uet-lahore-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'peshawar-university', 'profile' => 'hec-peshawar-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'ned', 'profile' => 'hec-ned-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'qau', 'profile' => 'hec-qau-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'nust', 'profile' => 'hec-nust-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'ucp', 'profile' => 'hec-ucp-institution-identity', 'authority' => 'HEC university identity'],
        ['id' => 'gcuf', 'profile' => 'hec-gcuf-institution-identity', 'authority' => 'HEC university identity'],
    ],
    'hec-campuses' => [
        ['id' => 'hec-recognized-campuses', 'url' => 'https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx', 'authority' => 'HEC recognized campus and parent relationship', 'extractor' => 'campus'],
    ],
    'provincial' => [
        ['id' => 'punjab-hed-public-universities', 'url' => 'https://hed.punjab.gov.pk/public-universities', 'authority' => 'Punjab HED public university directory', 'extractor' => 'unsupported-directory'],
        ['id' => 'sindh-universities-boards', 'url' => 'https://universitiesboards.sindh.gov.pk/universitiesinstitutes', 'authority' => 'Sindh Universities and Boards Department', 'extractor' => 'unsupported-directory'],
        ['id' => 'sindh-hec-academics', 'url' => 'https://sindhhec.gov.pk/academics-detail/', 'authority' => 'Sindh Higher Education Commission', 'extractor' => 'unsupported-directory'],
        ['id' => 'kp-hera-universities', 'url' => 'https://herakp.gov.pk/pages/university_list.php', 'authority' => 'KP Higher Education Regulatory Authority', 'extractor' => 'unsupported-directory'],
        ['id' => 'ict-education-health', 'url' => 'https://ictadministration.gov.pk/education-and-health/', 'authority' => 'ICT Administration education directory', 'extractor' => 'unsupported-directory'],
    ],
];
