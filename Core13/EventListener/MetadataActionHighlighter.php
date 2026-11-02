<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Core13\EventListener;

use FGTCLB\FileRequiredAttributes\EventListener\MetadataActionHighlighterInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

#[AsAlias(MetadataActionHighlighterInterface::class)]
final class MetadataActionHighlighter implements MetadataActionHighlighterInterface
{
    public function highlightMetadataAction(ProcessFileListActionsEvent $event): void
    {
        $actionItems = $event->getActionItems();
        $metadataAction = $actionItems['metadata'] ?? null;
        if (!$metadataAction instanceof LinkButton) {
            return;
        }
        $metadataAction->setClasses(trim($metadataAction->getClasses() . ' required-attributes-missing'));
        $event->setActionItems($actionItems);
    }
}
