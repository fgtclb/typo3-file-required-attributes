<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaLoadedEvent
{
    protected static int $typo3Version;

    protected static bool $overrideReferencePossible = false;

    public function __construct()
    {
        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_required_attributes'];
        self::$overrideReferencePossible = array_key_exists('virtualFields', $extensionConfiguration) && (bool)$extensionConfiguration['virtualFields'];
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        self::$typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $requiredColumns = RequiredColumnsUtility::getRequiredColumns();

        $table = 'sys_file_metadata';

        $loadedTca = $event->getTca();
        $sysFileMetadata = $loadedTca[$table];
        $columns = $sysFileMetadata['columns'];
        if (count($requiredColumns) > 0) {
            foreach ($loadedTca['sys_file_reference']['palettes'] as $paletteKey => $palette) {
                if (array_key_exists('isHiddenPalette', $palette)) {
                    continue;
                }
                $palette['showitem'] .= ',--linebreak--';
                $loadedTca['sys_file_reference']['palettes'][$paletteKey] = $palette;
            }
        }
        $requiredAttributesConfig = [];
        foreach ($requiredColumns as $requiredColumnConfig) {
            $requiredColumn = $requiredColumnConfig['columnName'];
            $fileTypes = $requiredColumnConfig['fileTypes'];
            if (!array_key_exists($requiredColumn, $columns)) {
                continue;
            }
            foreach ($fileTypes as $fileType) {
                $requiredAttributesConfig[$fileType][] = $requiredColumn;
            }
            if (in_array($columns[$requiredColumn]['config']['type'], RequiredColumnsUtility::$requiredSetColumns)) {
                if (12 > self::$typo3Version) {
                    $eval = $loadedTca[$table]['columns'][$requiredColumn]['config']['eval'] ?? '';
                    if (!str_contains($eval, 'required')) {
                        $evaluations = GeneralUtility::trimExplode(',', $eval);
                        $evaluations[] = 'required';
                        $evaluations = array_filter($evaluations, fn($value) => $value !== '');
                        $loadedTca[$table]['columns'][$requiredColumn]['config']['eval'] = implode(',', $evaluations);
                    }
                } else {
                    $loadedTca[$table]['columns'][$requiredColumn]['config']['required'] = true;
                }
            }
            $loadedTca = $this->createOrUpdateOverrideColumnForReference($requiredColumn, $columns[$requiredColumn], $loadedTca, $fileTypes);
        }
        $loadedTca[$table]['ctrl']['required_attributes'] = $requiredAttributesConfig;
        $event->setTca($loadedTca);
    }

    /**
     * @param array<string, mixed> $originalColumn
     * @param array<string, mixed> $loadedTca
     * @param int[] $fileTypes
     * @return array<string, mixed>
     */
    protected function createOrUpdateOverrideColumnForReference(
        string $columnName,
        array  $originalColumn,
        array  $loadedTca,
        array $fileTypes
    ): array
    {
        if (array_key_exists($columnName, $loadedTca['sys_file_reference']['columns'])) {
            return $this->updateColumnForReference($columnName, $loadedTca);
        }
        return $this->createColumnForReference($columnName, $originalColumn, $loadedTca, $fileTypes);
    }

    /**
     * @param array<string, mixed> $loadedTca
     * @return array<string, mixed>
     */
    protected function updateColumnForReference(
        string $columnName,
        array  $loadedTca
    ): array
    {
        //$loadedTca['sys_file_reference']['columns'][$columnName]['description'] = 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.description';
        return $loadedTca;
    }

    /**
     * @param array<string, mixed> $originalColumn
     * @param array<string, mixed> $loadedTca
     * @param int[] $fileTypes
     * @return array<string, mixed>
     */
    protected function createColumnForReference(
        string $columnName,
        array  $originalColumn,
        array  $loadedTca,
        array $fileTypes
    ): array
    {
        $virtualColumn = 'virtual_' . $columnName;
        $virtualColumnConfig = [
            'label' => $originalColumn['label'],
            'description' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.virtual.description',
            'config' => [
                'type' => 'user',
                'renderType' => 'fileRequiredAttributeShow',
                'parameters' => [
                    'originalField' => $columnName,
                    'override' => self::$overrideReferencePossible,
                ],
            ],
        ];
        $loadedTca['sys_file_reference']['columns'][$virtualColumn] = $virtualColumnConfig;
        $loadedTca['sys_file_reference']['palettes'] = $this->addColumnsToPalette($virtualColumn, $fileTypes, $loadedTca['sys_file_reference']['palettes']);
        // add override column, if set
        if (self::$overrideReferencePossible) {
            $additionalConfig = [
                'l10n_display' => 'defaultAsReadonly',
                'description' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.description',
            ];
            $config = match (true) {
                in_array($originalColumn['config']['type'], RequiredColumnsUtility::$overrideMethodNeeded) => $this->addOverrideMethod($columnName, $originalColumn),
                in_array($originalColumn['config']['type'], RequiredColumnsUtility::$requiredSetColumns) => $this->addOverridePlaceholder($columnName, $originalColumn)
            };

            $additionalConfig['config'] = $config;
            $newColumn = $originalColumn;
            ArrayUtility::mergeRecursiveWithOverrule($newColumn, $additionalConfig);
            $loadedTca['sys_file_reference']['columns'][$columnName] = $newColumn;
            $loadedTca['sys_file_reference']['palettes'] = $this->addColumnsToPalette($columnName, $fileTypes, $loadedTca['sys_file_reference']['palettes']);

        }

        // add linebreak for better UX
        $loadedTca['sys_file_reference']['palettes'] = $this->addColumnsToPalette('--linebreak--', $fileTypes, $loadedTca['sys_file_reference']['palettes']);
        return $loadedTca;
    }

    private function addOverrideMethod(string $columnName, array $originalColumn): array
    {
        return [];
    }

    private function addOverridePlaceholder(string $columnName, array $originalColumn): array
    {
        $config = [
            'mode' => 'useOrOverridePlaceholder',
            'placeholder' => sprintf('__row|uid_local|metadata|%s', $columnName),
            'default' => null,
        ];
        if (12 > self::$typo3Version) {
            $config['eval'] = 'null';
        } else {
            $config['nullable'] = true;
        }
        return $config;
    }

    /**
     * @param string $columnName
     * @param int[] $fileTypes
     * @param array<int|string, mixed> $palettes
     * @return array<int|string, mixed>
     */
    private function addColumnsToPalette(string $columnName, array $fileTypes, array $palettes): array
    {
        $palettesToAdd = [];
        foreach ($fileTypes as $fileType) {
            $palettesToAdd[] = RequiredColumnsUtility::$fileTypeToPaletteMapping[$fileType] ?? '';
        }

        $palettesToAdd = array_filter($palettesToAdd);

        if (empty($palettesToAdd)) {
            return $palettes;
        }

        foreach ($palettesToAdd as $paletteToAdd) {
            if (array_key_exists($paletteToAdd, $palettes)) {
                if (array_key_exists('isHiddenPalette', $palettes[$paletteToAdd])) {
                    continue;
                }
                $palettes[$paletteToAdd]['showitem'] .= sprintf(',%s', $columnName);
            }
        }

        return $palettes;
    }
}
