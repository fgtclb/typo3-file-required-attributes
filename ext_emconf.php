<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB File required attributes',
    'description' => 'Marks metadata fields required and disables file references if required fields are missing',
    'category' => 'fe,be',
    'state' => 'stable',
    'version' => '3.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.19-14.3.99',
            'backend' => '13.4.19-14.3.99',
            'filelist' => '13.4.19-14.3.99',
            'filemetadata' => '13.4.19-14.3.99',
        ],
    ],
];
