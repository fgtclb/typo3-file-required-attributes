<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

final class FileListActionsEvent
{
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly FlashMessageService $flashMessageService,
    ) {}

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if (!$event->isFile()) {
            return;
        }
        /** @var File $file */
        $file = $event->getResource();
        $meta = $file->getMetaData();
        $requiredMatrix = RequiredColumnsUtility::getRequiredColumnsFromTCA();
        $required = $requiredMatrix[$file->getType()] ?? [];
        $missing = false;
        foreach ($required as $column) {
            if (!$meta->offsetExists($column) || empty($meta->offsetGet($column))) {
                $missing = true;
            }
        }
        if ($missing) {
            $actionItems = $event->getActionItems();
            if (array_key_exists('metadata', $actionItems)) {
                $actionItems['metadata'] = str_replace('btn-default', 'btn-danger', $actionItems['metadata']);
                $event->setActionItems($actionItems);
            }
            $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL(
                    'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.notSet.body'
                ),
                sprintf(
                    $languageService->sL(
                        'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.notSet.header'
                    ),
                    $file->getName(),
                ),
                ContextualFeedbackSeverity::WARNING
            );
            $this->flashMessageService
                ->getMessageQueueByIdentifier()
                ->addMessage($flashMessage);
        }
    }
}
