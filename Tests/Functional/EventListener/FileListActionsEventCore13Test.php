<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\EventListener;

use FGTCLB\FileRequiredAttributes\EventListener\FileListActionsEvent;
use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

/**
 * TYPO3 v13 hands the file list actions over as one list keyed by action name.
 */
#[RequiresMethod(ProcessFileListActionsEvent::class, 'getActionItems')]
final class FileListActionsEventCore13Test extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/Files.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/AudioFiles.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function metadataActionOfAFileWithMissingCopyrightIsHighlighted(): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject(1),
            [
                'metadata' => GeneralUtility::makeInstance(LinkButton::class)->setClasses('btn-sm'),
                'delete' => null,
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        $actionItems = $event->getActionItems();
        self::assertInstanceOf(LinkButton::class, $actionItems['metadata']);
        self::assertSame('btn-sm required-attributes-missing', $actionItems['metadata']->getClasses());
        self::assertArrayHasKey('delete', $actionItems);
    }

    #[Test]
    public function fileWithMissingCopyrightQueuesAWarningEvenWithoutMetadataAction(): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject(1),
            [
                'metadata' => null,
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertNull($event->getActionItems()['metadata']);
        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame(ContextualFeedbackSeverity::WARNING, $messages[0]->getSeverity());
        self::assertSame('Missing required fields in "image-without-copyright.jpg"', $messages[0]->getTitle());
    }

    #[Test]
    #[DataProvider('radioColumnDataProvider')]
    public function radioColumnOnItsNotSetItemQueuesAWarning(int $fileUid, int $expectedMessages): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject($fileUid),
            [
                'metadata' => null,
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertCount(
            $expectedMessages,
            $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()
        );
    }

    public static function radioColumnDataProvider(): \Generator
    {
        yield 'audio with the right of use on its not set item' => [
            'fileUid' => 5,
            'expectedMessages' => 1,
        ];
        yield 'audio with a right of use' => [
            'fileUid' => 6,
            'expectedMessages' => 0,
        ];
    }

    #[Test]
    public function fileWithCopyrightIsLeftAlone(): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject(2),
            [
                'metadata' => GeneralUtility::makeInstance(LinkButton::class),
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertSame('', $event->getActionItems()['metadata']->getClasses());
        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function fileWithoutRequiredColumnsIsLeftAlone(): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject(3),
            [
                'metadata' => GeneralUtility::makeInstance(LinkButton::class),
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertSame('', $event->getActionItems()['metadata']->getClasses());
        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function folderIsLeftAlone(): void
    {
        $event = new ProcessFileListActionsEvent(
            $this->get(ResourceFactory::class)->getFileObject(1)->getParentFolder(),
            [
                'metadata' => GeneralUtility::makeInstance(LinkButton::class),
            ]
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }
}
