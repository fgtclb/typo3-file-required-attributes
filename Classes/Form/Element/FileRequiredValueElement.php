<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Form\Element;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Backend\Form\Behavior\UpdateValueOnFieldChange;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileRequiredValueElement extends AbstractFormElement
{
    /**
     * @inheritDoc
     * @return array{html?: string}
     */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $originalField = $parameterArray['fieldConf']['config']['parameters']['originalField'];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);

        $originalRow = BackendUtility::getRecord(
            'sys_file_reference',
            $row['uid'],
            'uid_local'
        );
        if ($originalRow === null) {
            return $resultArray;
        }
        $metaData = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata')
            ->select(
                ['*'],
                'sys_file_metadata',
                [
                    'file' => $originalRow['uid_local'],
                ],
            )
            ->fetchAssociative() ?: [
            'uid' => null,
            $originalField => '',
        ];

        $originalFieldConfig = $GLOBALS['TCA']['sys_file_metadata']['columns'][$originalField] ??= [];

        // get the value from the original field
        $value = $metaData[$originalField] ?? '';

        // if radio, select, check, get value from language
        if (in_array($originalFieldConfig['config']['type'] ?? '', RequiredColumnsUtility::$overrideMethodNeeded)) {
            if (method_exists(self::class, $originalFieldConfig['config']['type'])) {
                $value = $this->{$originalFieldConfig['config']['type']}($value, $originalFieldConfig);
            }
        }

        if ($originalFieldConfig !== []) {
            $itemFormElName = sprintf('data[sys_file_metadata][%d][%s]', $metaData['uid'],  $originalField);
            $newNodeData = [
                'renderType' => $originalFieldConfig['config']['renderType'] ?? $originalFieldConfig['config']['type'],
                'tableName' => 'sys_file_metadata',
                'fieldName' => $originalField,
                'parameterArray' => [
                    'fieldChangeFunc' => [
                        'TBE_EDITOR_fieldChanged' => new UpdateValueOnFieldChange(
                            'sys_file_metadata',
                            (string)$metaData['uid'],
                            $originalField,
                            $itemFormElName
                        )
                    ],
                    'itemFormElName' => $itemFormElName,
                    // set given value to null, if a value is selected, avoiding the
                    // processor setting the override checkbox enabled
                    'itemFormElValue' => $value === '' ? $value : null,
                    'fieldConf' => [
                        'config' => [
                            'renderType' => $originalFieldConfig['config']['renderType'] ?? '',
                            'type' => $originalFieldConfig['config']['type'],
                            'mode' => 'useOrOverridePlaceholder',
                            'nullable' => true,
                            'placeholder' => $metaData[$originalField],
                            'required' => $value === '',
                            'items' => $originalFieldConfig['config']['items'] ?? null,
                        ],
                        'description' => $parameterArray['fieldConf']['description'] ?? '',
                        'label' => $parameterArray['fieldConf']['label'] ?? '',
                    ],
                ],
                'processedTca' => $GLOBALS['TCA']['sys_file_metadata'] ?? [],
                'databaseRow' => $metaData,
                'recordTypeValue' => null,
            ];

            // process possible not processed labels
            $newNodeData = GeneralUtility::makeInstance(TcaColumnsProcessFieldLabels::class)->addData($newNodeData);

            // check, if the element has sub labels like items
            $subItemTranslationClass = match ($newNodeData['renderType']) {
                'check' => TcaCheckboxItems::class,
                'select' => TcaSelectItems::class,
                'radio' => TcaRadioItems::class,
                default => ''
            };
            // if the match is given, process the language translation of labels
            // for selectable items
            if (is_a($subItemTranslationClass,AbstractItemProvider::class, true)) {
                $newNodeData = GeneralUtility::makeInstance($subItemTranslationClass)->addData($newNodeData);
                $newNodeData['parameterArray']['fieldConf']['config']['items'] = $newNodeData['processedTca']['columns'][$originalField]['config']['items'] ?? $newNodeData['parameterArray']['fieldConf']['config']['items'];
                // if select, checkbox od radio, set the value
                // showing the correct value, other than text or input, this has to be done
                // avoiding misunderstanding values and possibly enforcing the user setting
                // the value again
                $newNodeData['parameterArray']['itemFormElValue'] = $value;
            }

            return $this->nodeFactory->create($newNodeData)->render();
        }

        // no node found, field is readOnly
        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =      '<div class="form-wizards-element">';
        $html[] =         '<div class="form-control-wrap">';
        $html[] =            sprintf('<p>%s</p>', $value);
        $html[] =         '</div>';
        $html[] =      '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }
}
