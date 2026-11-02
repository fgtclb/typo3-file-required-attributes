<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\Hooks;

use FGTCLB\FileRequiredAttributes\Exception\InvalidFileReferenceException;
use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * With virtual fields enabled, a required metadata column also becomes an overridable column of the
 * file reference, so its value can be given on the reference itself. That value is written to the
 * metadata of the referenced file, although the core creates a database column on the reference too.
 */
final class FileReferenceRequiredFieldsHookTest extends AbstractFunctionalTestCase
{
    private bool $cli = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/Files.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/AudioFiles.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExistingReferences.csv');
        $this->setUpBackendUser(1);
        $this->cli = Environment::isCli();
    }

    protected function tearDown(): void
    {
        $this->setCliEnvironment($this->cli);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $reference
     */
    #[Test]
    #[DataProvider('newReferenceVisibilityDataProvider')]
    public function newReferenceIsHiddenOnlyWhenARequiredColumnIsMissing(array $reference, int $expectedHidden): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => array_merge(
                        [
                            'pid' => 1,
                            'uid_foreign' => 1,
                            'tablenames' => 'pages',
                            'fieldname' => 'media',
                        ],
                        $reference
                    ),
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $row = $this->fetchReference((int)$dataHandler->substNEWwithIDs['NEW1']);
        self::assertSame($expectedHidden, (int)$row['hidden']);
    }

    public static function newReferenceVisibilityDataProvider(): \Generator
    {
        yield 'image without copyright is hidden' => [
            'reference' => [
                'uid_local' => 1,
            ],
            'expectedHidden' => 1,
        ];
        yield 'image without copyright given as combined table and uid is hidden' => [
            'reference' => [
                'uid_local' => 'sys_file_1',
            ],
            'expectedHidden' => 1,
        ];
        yield 'image without copyright and without metadata record is hidden' => [
            'reference' => [
                'uid_local' => 4,
            ],
            'expectedHidden' => 1,
        ];
        yield 'image with copyright in the metadata stays visible' => [
            'reference' => [
                'uid_local' => 2,
            ],
            'expectedHidden' => 0,
        ];
        yield 'image with copyright given on the reference stays visible' => [
            'reference' => [
                'uid_local' => 1,
                'copyright' => 'John Doe',
            ],
            'expectedHidden' => 0,
        ];
        yield 'document without required columns stays visible' => [
            'reference' => [
                'uid_local' => 3,
            ],
            'expectedHidden' => 0,
        ];
        yield 'image without copyright stays hidden when saved hidden' => [
            'reference' => [
                'uid_local' => 1,
                'hidden' => 1,
            ],
            'expectedHidden' => 1,
        ];
        yield 'audio with the right of use on its not set item is hidden' => [
            'reference' => [
                'uid_local' => 5,
            ],
            'expectedHidden' => 1,
        ];
        yield 'audio with a right of use stays visible' => [
            'reference' => [
                'uid_local' => 6,
            ],
            'expectedHidden' => 0,
        ];
        yield 'image without copyright stays visible when a listener drops the required columns' => [
            'reference' => [
                'uid_local' => 1,
                'title' => 'skip-required-check',
            ],
            'expectedHidden' => 0,
        ];
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
        $row = $this->fetchReference((int)$dataHandler->substNEWwithIDs['NEW1']);
        self::assertSame(0, (int)$row['hidden']);
        self::assertSame('', (string)$row['copyright']);
        self::assertSame('John Doe', $this->fetchMetadata(1)['copyright']);
    }

    #[Test]
    #[DataProvider('existingReferenceDataProvider')]
    public function updatingAnExistingReferenceChecksTheReferencedFile(int|string $referenceId, int $uid, int $expectedHidden): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    $referenceId => [
                        'title' => 'changed title',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $row = $this->fetchReference($uid);
        self::assertSame('changed title', $row['title']);
        self::assertSame($expectedHidden, (int)$row['hidden']);
    }

    public static function existingReferenceDataProvider(): \Generator
    {
        yield 'reference to an image without copyright is hidden' => [
            'referenceId' => 1,
            'uid' => 1,
            'expectedHidden' => 1,
        ];
        yield 'reference to an image with copyright stays visible' => [
            'referenceId' => 2,
            'uid' => 2,
            'expectedHidden' => 0,
        ];
    }

    #[Test]
    public function newReferenceWithUnusableFileIdentifierIsRejected(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 'sys_file_abc',
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                    ],
                ],
            ],
            []
        );

        $this->expectException(InvalidFileReferenceException::class);
        $this->expectExceptionCode(1684163297042);

        $dataHandler->process_datamap();
    }

    #[Test]
    public function hidingAReferenceOnTheCommandLineQueuesNoFlashMessage(): void
    {
        $this->setCliEnvironment(true);
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
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertSame([], $messages);
    }

    #[Test]
    public function hidingAReferenceInTheBackendQueuesAWarningNamingFileAndColumn(): void
    {
        $this->setCliEnvironment(false);
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
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame(ContextualFeedbackSeverity::WARNING, $messages[0]->getSeverity());
        self::assertSame('Not all required fields set!', $messages[0]->getTitle());
        self::assertStringContainsString('"image-without-copyright.jpg"', $messages[0]->getMessage());
        self::assertStringContainsString('"Copyright"', $messages[0]->getMessage());
    }

    #[Test]
    public function savingAVisibleReferenceInTheBackendQueuesNoFlashMessage(): void
    {
        $this->setCliEnvironment(false);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file_reference' => [
                    'NEW1' => [
                        'pid' => 1,
                        'uid_local' => 2,
                        'uid_foreign' => 1,
                        'tablenames' => 'pages',
                        'fieldname' => 'media',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertSame([], $messages);
    }

    /**
     * The hook only queues flash messages outside the command line, and a functional test always
     * runs on it.
     */
    private function setCliEnvironment(bool $cli): void
    {
        Environment::initialize(
            Environment::getContext(),
            $cli,
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchReference(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($row);
        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchMetadata(int $fileUid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($row);
        return $row;
    }
}
