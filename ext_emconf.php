<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB File required attributes',
    'description' => 'Marks metadata fields required and disables file references if required fields are missing',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.99.99',
            'backend' => '14.0.0-14.99.99',
            'filelist' => '14.0.0-14.99.99',
            'filemetadata' => '14.0.0-14.99.99',
        ],
    ],
];
