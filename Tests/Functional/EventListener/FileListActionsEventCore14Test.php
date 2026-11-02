<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\EventListener;

use FGTCLB\FileRequiredAttributes\EventListener\FileListActionsEvent;
use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ComponentGroup;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

/**
 * TYPO3 v14 groups the file list actions into a primary and a secondary group.
 */
#[RequiresMethod(ProcessFileListActionsEvent::class, 'getActionGroup')]
final class FileListActionsEventCore14Test extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/Files.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataSet/AudioFiles.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function primaryMetadataActionOfAFileWithMissingCopyrightIsHighlighted(): void
    {
        $primary = new ComponentGroup('primary');
        $primary->add('metadata', GeneralUtility::makeInstance(LinkButton::class)->setClasses('btn-sm'));
        $event = new ProcessFileListActionsEvent(
            $primary,
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject(1),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        $metadataAction = $event->getAction('metadata', ActionGroup::primary);
        self::assertInstanceOf(LinkButton::class, $metadataAction);
        self::assertSame('btn-sm required-attributes-missing', $metadataAction->getClasses());
    }

    #[Test]
    public function secondaryMetadataActionOfAFileWithMissingCopyrightIsMovedToThePrimaryGroupAndHighlighted(): void
    {
        $secondary = new ComponentGroup('secondary');
        $secondary->add('metadata', GeneralUtility::makeInstance(LinkButton::class));
        $event = new ProcessFileListActionsEvent(
            new ComponentGroup('primary'),
            $secondary,
            $this->get(ResourceFactory::class)->getFileObject(1),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertFalse($event->hasAction('metadata', ActionGroup::secondary));
        $metadataAction = $event->getAction('metadata', ActionGroup::primary);
        self::assertInstanceOf(LinkButton::class, $metadataAction);
        self::assertSame('required-attributes-missing', $metadataAction->getClasses());
    }

    #[Test]
    public function fileWithMissingCopyrightQueuesAWarningEvenWithoutMetadataAction(): void
    {
        $event = new ProcessFileListActionsEvent(
            new ComponentGroup('primary'),
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject(1),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertFalse($event->hasAction('metadata'));
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
            new ComponentGroup('primary'),
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject($fileUid),
            new ServerRequest('https://example.com/typo3/module/file/list')
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
        $primary = new ComponentGroup('primary');
        $primary->add('metadata', GeneralUtility::makeInstance(LinkButton::class));
        $event = new ProcessFileListActionsEvent(
            $primary,
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject(2),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        $metadataAction = $event->getAction('metadata', ActionGroup::primary);
        self::assertInstanceOf(LinkButton::class, $metadataAction);
        self::assertSame('', $metadataAction->getClasses());
        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function fileWithoutRequiredColumnsIsLeftAlone(): void
    {
        $primary = new ComponentGroup('primary');
        $primary->add('metadata', GeneralUtility::makeInstance(LinkButton::class));
        $event = new ProcessFileListActionsEvent(
            $primary,
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject(3),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        $metadataAction = $event->getAction('metadata', ActionGroup::primary);
        self::assertInstanceOf(LinkButton::class, $metadataAction);
        self::assertSame('', $metadataAction->getClasses());
        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function folderIsLeftAlone(): void
    {
        $primary = new ComponentGroup('primary');
        $primary->add('metadata', GeneralUtility::makeInstance(LinkButton::class));
        $event = new ProcessFileListActionsEvent(
            $primary,
            new ComponentGroup('secondary'),
            $this->get(ResourceFactory::class)->getFileObject(1)->getParentFolder(),
            new ServerRequest('https://example.com/typo3/module/file/list')
        );

        $this->get(FileListActionsEvent::class)($event);

        self::assertSame([], $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages());
    }
}
