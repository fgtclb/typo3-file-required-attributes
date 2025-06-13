<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB File required attributes',
    'description' => 'Marks metadata fields required and disables file references if required fields are missing',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '2.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'filelist' => '12.4.0-13.4.99',
            'filemetadata' => '12.4.0-13.4.99',
        ],
    ],
];
