<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\EventListener;

use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

/**
 * Marks the "edit metadata" action of a file list row, so a file with missing required metadata
 * stands out. The actions of the event are structured differently per TYPO3 major.
 */
interface MetadataActionHighlighterInterface
{
    public function highlightMetadataAction(ProcessFileListActionsEvent $event): void;
}
