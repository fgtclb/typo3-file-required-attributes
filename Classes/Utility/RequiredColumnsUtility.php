<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Utility;

use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @class RequiredColumnsUtility
 */
final class RequiredColumnsUtility
{
    /**
     * Holds as required registered fields
     * @var array<int, array{columnName: string, fileTypes: int[]}>
     */
    private static array $requiredColumns = [];

    /**
     * @var array<string, mixed>
     */
    private static array $registeredColumnsInTCA = [];

    /**
     * Fields where required attribute can be set
     * @var string[]
     */
    public static array $requiredSetColumns = [
        'text',
        'input',
    ];

    /**
     * Fields for override method
     * Fields for getting label fron TCA
     * @var string[]
     */
    public static array $overrideMethodNeeded = [
        'radio',
        'check',
        'select',
    ];

    /**
     * @var array<int, string>
     */
    public static array $fileTypeToPaletteMapping = [
        AbstractFile::FILETYPE_UNKNOWN => 'basicoverlayPalette',
        AbstractFile::FILETYPE_TEXT => 'basicoverlayPalette',
        AbstractFile::FILETYPE_IMAGE => 'imageoverlayPalette',
        AbstractFile::FILETYPE_AUDIO => 'audioOverlayPalette',
        AbstractFile::FILETYPE_VIDEO => 'videoOverlayPalette',
        AbstractFile::FILETYPE_APPLICATION => 'basicoverlayPalette',
    ];

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public static function loadConfigFromExt(): void
    {
        $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('file_required_attributes');
    }

    /**
     * registers a metadata field as required.
     * For call in TCA/Overrides/sys_file_metadata.php
     * @param int[] $fileTypes Require on fileTypes,
     * @see \TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_*
     * @@throws RuntimeException
     */
    public static function register(string $columnName, array $fileTypes): void
    {
        self::loadTCA();
        if (!array_key_exists($columnName, self::$registeredColumnsInTCA)) {
            throw new RuntimeException(
                sprintf('Column "%s" not registered in TCA', $columnName),
                1681395576121
            );
        }
        // Column already registered. Do nothing
        if (in_array($columnName, self::$requiredColumns)) {
            return;
        }
        self::$requiredColumns[] = [
            'columnName' => $columnName,
            'fileTypes' => $fileTypes,
        ];
    }

    /**
     * @return array<int, array{columnName: string, fileTypes: string[]}>
     */
    public static function getRequiredColumns(): array
    {
        return self::$requiredColumns;
    }

    /**
     * @return array<int, string>
     */
    public static function getRequiredColumnsFromTCA(): array
    {
        return $GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes'] ?? [];
    }

    private static function loadTCA(): void
    {
        if (!empty(self::$registeredColumnsInTCA)) {
            return;
        }
        $metaDataTCA = $GLOBALS['TCA']['sys_file_metadata'];
        self::$registeredColumnsInTCA = $metaDataTCA['columns'];
    }
}
