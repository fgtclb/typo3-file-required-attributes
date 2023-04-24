<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '(web-vision) Site Template',
    'description' => 'Marks metadata fields required and disables file references if required fields are missing',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'FGTCLB\\FileRequiredAttributes\\' => 'Classes/',
        ],
    ],
    'autoload-dev' => [
        'psr-4' => [
            'FGTCLB\\FileRequiredAttributes\\Tests\\' => 'Tests/',
        ],
    ],
];
