<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\Hooks;

use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Without virtual fields, a required metadata column is no column of the file reference, so a value
 * given on the reference is written to the metadata of the referenced file.
 */
final class FileReferenceRequiredFieldsHookWithoutVirtualFieldsTest extends AbstractFunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'file_required_attributes' => [
                'virtualFields' => '0',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/Files.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function copyrightGivenOnTheReferenceIsWrittenToTheMetadata(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 1,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                        'copyright' => 'John Doe',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CopyrightWrittenToMetadata.csv');
    }

    #[Test]
    public function copyrightGivenOnTheReferenceCreatesTheMissingMetadataRecordOfTheFile(): void
    {
        // Creating a metadata record makes the core index the file, which must therefore exist.
        GeneralUtility::mkdir_deep($this->instancePath . '/fileadmin');
        copy(__DIR__ . '/../Fixtures/Files/image.jpg', $this->instancePath . '/fileadmin/image-without-metadata.jpg');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 4,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                        'copyright' => 'John Doe',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/MetadataRecordCreated.csv');
    }

    #[Test]
    public function twoNewReferencesToAFileWithoutMetadataRecordCreateASingleOne(): void
    {
        // Creating a metadata record makes the core index the file, which must therefore exist.
        GeneralUtility::mkdir_deep($this->instancePath . '/fileadmin');
        copy(__DIR__ . '/../Fixtures/Files/image.jpg', $this->instancePath . '/fileadmin/image-without-metadata.jpg');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 4,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                        'copyright' => 'John Doe',
                    ],
                    'NEW2' => [
                        'pid' => 1,
                        'uid_local' => 4,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SingleMetadataRecordCreated.csv');
    }

    #[Test]
    public function emptyCopyrightGivenOnTheReferenceHidesTheReference(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 1,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                        'copyright' => '  ',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $hidden = $queryBuilder
            ->select('hidden')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$dataHandler->substNEWwithIDs['NEW1'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertSame(1, (int)$hidden);
    }
}
