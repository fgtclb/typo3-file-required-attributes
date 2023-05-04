<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Utility;

use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @class RequiredColumnsUtility
 */
final class RequiredColumnsUtility
{
    /**
     * Holds as required registered fields
     * @var array<int, string>
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
     * @@throws RuntimeException
     */
    public static function register(string $columnName): void
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
        self::$requiredColumns[] = $columnName;
        self::$requiredColumns = array_unique(self::$requiredColumns);
    }

    /**
     * @return array<int, string>
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
