<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Service\MetadataService;
use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TcaLoadedEvent
{
    protected static int $typo3Version;

    private const DEFAULT_REQUIRED_CONFIGURATION = [
        'type' => 'user',
        'renderType' => 'fileRequiredElement',
        'parameters' => [
            'originalField' => '',
            'originalConfiguration' => [],
            'override' => false,
        ]
    ];

    public function __construct(
        private readonly MetadataService $metadataService
    ) {
        self::$typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
    }

    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $requiredColumns = RequiredColumnsUtility::getRequiredColumns();

        $loadedTca = $event->getTca();

        $requiredAttributesConfig = [];
        foreach ($requiredColumns as $requiredColumnConfig) {
            $requiredColumn = $requiredColumnConfig['columnName'];
            if (!$this->metadataService->isFieldInMetadata($requiredColumn)) {
                continue;
            }
            $fileTypes = $requiredColumnConfig['fileTypes'];
            $override  = $requiredColumnConfig['override'];

            $this->makeMetadataFieldRequired($loadedTca, $requiredAttributesConfig, $requiredColumn, $fileTypes);

            $this->createOrUpdateOverrideColumnForReference($loadedTca, $requiredColumn, $fileTypes, $override);
        }
        $loadedTca[MetadataService::SYS_FILE_METADATA]['ctrl']['required_attributes'] = $requiredAttributesConfig;
        $event->setTca($loadedTca);
    }

    /**
     * @param array<string, mixed> $loadedTca
     * @param int[] $fileTypes
     */
    private function createOrUpdateOverrideColumnForReference(
        array  &$loadedTca,
        string $columnName,
        array $fileTypes,
        bool $override
    ): void {
        // override means the column can be overridden from reference to metadata
        // always enabled for virtual fields, existing fields must explicitly set
        $override = $override || !$this->metadataService->isFieldInReference($columnName);
        $columnsConfig = self::DEFAULT_REQUIRED_CONFIGURATION;
        $columnsConfig['parameters']['override'] = $override;
        $columnsConfig['parameters']['originalField'] = $columnName;
        $columnsConfig['parameters']['originalConfiguration'] = $loadedTca[MetadataService::SYS_FILE_METADATA]['columns'][$columnName] ?? [];

        $loadedTca[MetadataService::SYS_FILE_REFERENCE]['columns'][$columnName]['label'] ??= $loadedTca[MetadataService::SYS_FILE_METADATA]['columns'][$columnName]['label'];
        $loadedTca[MetadataService::SYS_FILE_REFERENCE]['columns'][$columnName]['config'] = $columnsConfig;

        if (!$this->metadataService->isFieldInReference($columnName)) {
            $loadedTca[MetadataService::SYS_FILE_REFERENCE]['palettes'] =
                $this->addColumnsToPalette(
                    $columnName,
                    $fileTypes,
                    $loadedTca[MetadataService::SYS_FILE_REFERENCE]['palettes'] ?? []
                );
        }
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

    private function makeMetadataFieldRequired(array &$loadedTca, array &$requiredAttributesConfig, string $fieldName, array $fileTypes): void
    {
        foreach ($fileTypes as $fileType) {
            $requiredAttributesConfig[$fileType][] = $fieldName;
            if (in_array($loadedTca[MetadataService::SYS_FILE_METADATA]['columns'][$fieldName]['config']['type'], RequiredColumnsUtility::$requiredSetColumns)) {
                if (self::$typo3Version < 12) {
                    $eval = $loadedTca[MetadataService::SYS_FILE_METADATA]['columns'][$fieldName]['config']['eval'] ?? '';
                    $evaluations = GeneralUtility::trimExplode(',', $eval, true);
                    $evaluations[] = 'required';
                    $evaluations = array_filter(array_unique($evaluations), fn($value) => $value !== '');
                    $loadedTca[MetadataService::SYS_FILE_METADATA]['types'][$fileType]['columnsOverrides'][$fieldName]['config']['eval'] = implode(',', $evaluations);
                } else {
                    $loadedTca[MetadataService::SYS_FILE_METADATA]['types'][$fileType]['columnsOverrides'][$fieldName]['config']['required'] = true;
                }
            }
        }
    }
}
