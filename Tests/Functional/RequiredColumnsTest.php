<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional;

use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use FGTCLB\FileRequiredAttributes\Resource\FileType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RequiredColumnsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-filelist',
        'typo3/cms-filemetadata',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/file-required-attributes',
        'typo3conf/ext/file_required_attributes/Tests/Functional/Fixtures/Extensions/test_extension',
    ];

    #[Test]
    public function configuredFieldsAreSetRequired(): void
    {
        self::assertIsArray($GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes']);
        self::assertIsArray($GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes'][FileType::IMAGE]);
        self::assertContains('copyright', $GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes'][FileType::IMAGE]);
        self::assertContains('alternative', $GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes'][FileType::IMAGE]);
    }

    #[Test]
    public function virtualFieldsAreGenerated(): void
    {
        self::assertIsArray($GLOBALS['TCA']['sys_file_reference']['columns']['copyright']);
        self::assertIsArray($GLOBALS['TCA']['sys_file_reference']['columns']['copyright']['config']);
        self::assertArrayHasKey('type', $GLOBALS['TCA']['sys_file_reference']['columns']['copyright']['config']);
        self::assertEquals('none', $GLOBALS['TCA']['sys_file_reference']['columns']['copyright']['config']['type']);
    }

    #[Test]
    public function virtualFieldsAreNotCreatedInDatabase(): void
    {
        self::expectException(ColumnDoesNotExist::class);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference')
            ->getSchemaInformation()
            ->introspectTable('sys_file_reference')
            ->getColumn('copyright');
    }
}
