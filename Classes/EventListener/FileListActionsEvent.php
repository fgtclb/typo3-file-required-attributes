<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

class FileListActionsEvent
{
    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if (!$event->isFile()) {
            return;
        }
        /** @var File $file */
        $file = $event->getResource();
        $meta = $file->getMetaData();
        $required = RequiredColumnsUtility::getRequiredColumnsFromTCA();
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
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                LocalizationUtility::translate(
                    'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.notSet.body'
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:file_required_attributes/Resources/Private/Language/locallang_be.xlf:sys_file_metadata.notSet.header',
                    null,
                    [
                        $file->getName(),
                    ]
                ),
                FlashMessage::WARNING
            );
            $flashMessageService->getMessageQueueByIdentifier()->addMessage($flashMessage);
        }
    }
}
