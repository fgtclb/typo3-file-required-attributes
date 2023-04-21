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
    /**
     * @var string[]
     */
    protected static array $requiredSetColumns = [
        'text',
        'input',
    ];

    protected static int $typo3Version;

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        self::$typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $requiredColumns = RequiredColumnsUtility::getRequiredColumns();

        $table = 'sys_file_metadata';

        $loadedTca = $event->getTca();
        $sysFileMetadata = $loadedTca[$table];
        $columns = $sysFileMetadata['columns'];
        foreach ($requiredColumns as $requiredColumn) {
            if (!array_key_exists($requiredColumn, $columns)) {
                continue;
            }
            if (in_array($columns[$requiredColumn]['config']['type'], self::$requiredSetColumns)) {
                if (12 > self::$typo3Version) {
                    $eval = $loadedTca[$table]['columns'][$requiredColumn]['config']['eval'] ?? '';
                    if (!str_contains($eval, 'required')) {
                        $evaluations = GeneralUtility::trimExplode(',', $eval);
                        $evaluations[] = 'required';
                        $evaluations = array_filter($evaluations, fn ($value) => $value !== '');
                        $loadedTca[$table]['columns'][$requiredColumn]['config']['eval'] = implode(',', $evaluations);
                    }
                } else {
                    $loadedTca[$table]['columns'][$requiredColumn]['config']['required'] = true;
                }
            }
            $loadedTca = $this->createOrUpdateOverrideColumnForReference($requiredColumn, $columns[$requiredColumn], $loadedTca);
        }
        $event->setTca($loadedTca);
    }

    /**
     * @param array<string, mixed> $originalColumn
     * @param array<string, mixed> $loadedTca
     * @return array<string, mixed>
     */
    protected function createOrUpdateOverrideColumnForReference(
        string $columnName,
        array $originalColumn,
        array $loadedTca
    ): array {
        if (array_key_exists($columnName, $loadedTca['sys_file_reference']['columns'])) {
            return $this->updateColumnForReference($columnName, $loadedTca);
        }
        return $this->createColumnForReference($columnName, $originalColumn, $loadedTca);
    }

    /**
     * @param array<string, mixed> $originalColumn
     * @param array<string, mixed> $loadedTca
     * @return array<string, mixed>
     */
    protected function createColumnForReference(
        string $columnName,
        array $originalColumn,
        array $loadedTca
    ): array {
        $additionalConfig = [
            'l10n_display' => 'defaultAsReadonly',
            'description' => 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.description',
            'config' => [
                'mode' => 'useOrOverridePlaceholder',
                'placeholder' => sprintf('__row|uid_local|metadata|%s', $columnName),
                'default' => null,
            ],
        ];
        if (in_array($originalColumn['config']['type'], self::$requiredSetColumns)) {
            if (12 > self::$typo3Version) {
                $additionalConfig['config']['eval'] = 'null';
            } else {
                $additionalConfig['config']['nullable'] = true;
            }
        }
        $newColumn = $originalColumn;
        ArrayUtility::mergeRecursiveWithOverrule($newColumn, $additionalConfig);
        $loadedTca['sys_file_reference']['columns'][$columnName] = $newColumn;
        foreach ($loadedTca['sys_file_reference']['palettes'] as $paletteKey => $palette) {
            if (array_key_exists('isHiddenPalette', $palette)) {
                continue;
            }
            $palette['showitem'] .= sprintf(',%s', $columnName);
            $loadedTca['sys_file_reference']['palettes'][$paletteKey] = $palette;
        }
        return $loadedTca;
    }

    /**
     * @param array<string, mixed> $loadedTca
     * @return array<string, mixed>
     */
    protected function updateColumnForReference(
        string $columnName,
        array $loadedTca
    ): array {
        //$loadedTca['sys_file_reference']['columns'][$columnName]['description'] = 'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_reference.global.description';
        return $loadedTca;
    }
}
