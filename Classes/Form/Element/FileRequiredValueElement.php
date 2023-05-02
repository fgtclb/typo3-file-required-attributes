<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileRequiredValueElement extends AbstractFormElement
{

    /**
     * @inheritDoc
     */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $originalField = $parameterArray['fieldConf']['config']['parameters']['originalField'];
        $overridePossible = $parameterArray['fieldConf']['config']['parameters']['override'];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);

        $originalRow = BackendUtility::getRecord(
            'sys_file_reference',
            $row['uid'],
            'uid_local'
        );
        $metaData = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata')
            ->select(
                [$originalField],
                'sys_file_metadata',
                [
                    'file' => $originalRow['uid_local'],
                ],
            )
            ->fetchAssociative();

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =      '<div class="form-wizards-element">';
        $html[] =         '<div class="form-control-wrap">';
        $html[] =            sprintf('<p>%s</p>', $metaData[$originalField] ?? '[empty]');
        $html[] =         '</div>';
        $html[] =      '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }
}
