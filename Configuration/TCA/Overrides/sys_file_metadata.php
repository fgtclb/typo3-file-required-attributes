<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    $newColumns = [
        'right_of_use' => [
            'label' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.notSet',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.full',
                        'value' => 1,
                    ],
                    [
                        'label' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.limited',
                        'value' => 2,
                    ],
                ],
            ],
        ],
    ];
    ExtensionManagementUtility::addTCAcolumns(
        'sys_file_metadata',
        $newColumns
    );
    ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_metadata',
        'right_of_use',
        '',
        'after:description'
    );
})();
