<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
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
        if ($missing && $event->hasAction('metadata', ActionGroup::primary)) {
            $metadataAction = $event->getAction('metadata', ActionGroup::primary);
            if ($metadataAction instanceof LinkButton) {
                $metadataAction->setClasses('required-attributes-missing');
                $event->setAction($metadataAction, 'metadata', ActionGroup::primary);
            }
            $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL(
                    'file_required_attributes.be:sys_file_metadata.notSet.body'
                ),
                sprintf(
                    $languageService->sL(
                        'file_required_attributes.be:sys_file_metadata.notSet.header'
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
