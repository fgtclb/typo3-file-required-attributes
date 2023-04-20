<?php

(static function (): void {
    $newColumns = [
        'right_of_use' => [
            'label' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.notSet',
                        0,
                    ],
                    [
                        'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.full',
                        1,
                    ],
                    [
                        'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.right_of_use.limited',
                        2,
                    ],
                ],
            ],
        ],
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'sys_file_metadata',
        $newColumns
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_metadata',
        'right_of_use',
        '',
        'after:description'
    );
})();
