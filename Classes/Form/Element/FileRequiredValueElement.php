<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Form\Element;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
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
        $overridePossible = $parameterArray['fieldConf']['config']['parameters']['override'];

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
                [$originalField],
                'sys_file_metadata',
                [
                    'file' => $originalRow['uid_local'],
                ],
            )
            ->fetchAssociative();

        $originalFieldConfig = $GLOBALS['TCA']['sys_file_metadata']['columns'][$originalField] ??= [];

        // get the value from the original field
        $value = $metaData[$originalField] ?? '[empty]';

        // if radio, select, check, get value from language
        if (in_array($originalFieldConfig['config']['type'] ?? '', RequiredColumnsUtility::$overrideMethodNeeded)) {
            if (method_exists(self::class, $originalFieldConfig['config']['type'])) {
                $value = $this->{$originalFieldConfig['config']['type']}($value, $originalFieldConfig);
            }
        }

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

    /**
     * @todo implement checkboxes
     * @param array<int|string, mixed> $config
     */
    private function check(mixed $value, array $config): string
    {
        foreach ($config['config']['items'] ?? [] as $item) {
            //
        }
        return '';
    }

    /**
     * @param array<int|string, mixed> $config
     */
    private function radio(mixed $value, array $config): string
    {
        $label = '';
        $item = $config['config']['items'][(int)$value] ??= [];
        if (count($item) > 1) {
            $localLangLabel = array_key_exists('label', $item) ? $item['label'] : $item[0];
            $label = $this->getLanguageService()->sL($localLangLabel);
        }

        return $label;
    }

    /**
     * @param array<int|string, mixed> $config
     */
    private function select(mixed $value, array $config): string
    {
        $label = '';
        $item = $config['config']['items'][(int)$value] ??= [];
        if (count($item) > 1) {
            $localLangLabel = array_key_exists('label', $item) ? $item['label'] : $item[0];
            $label = $this->getLanguageService()->sL($localLangLabel);
        }

        return $label;
    }
}
