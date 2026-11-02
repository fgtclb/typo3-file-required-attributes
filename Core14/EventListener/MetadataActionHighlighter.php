<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Core14\EventListener;

use FGTCLB\FileRequiredAttributes\EventListener\MetadataActionHighlighterInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

#[AsAlias(MetadataActionHighlighterInterface::class)]
final class MetadataActionHighlighter implements MetadataActionHighlighterInterface
{
    public function highlightMetadataAction(ProcessFileListActionsEvent $event): void
    {
        if (!$event->hasAction('metadata')) {
            return;
        }
        // A secondary action is rendered as a plain dropdown item, which cannot be highlighted.
        if (!$event->hasAction('metadata', ActionGroup::primary)) {
            $event->moveActionTo('metadata', ActionGroup::primary);
        }
        $metadataAction = $event->getAction('metadata', ActionGroup::primary);
        if (!$metadataAction instanceof LinkButton) {
            return;
        }
        $metadataAction->setClasses(trim($metadataAction->getClasses() . ' required-attributes-missing'));
        $event->setAction($metadataAction, 'metadata', ActionGroup::primary);
    }
}
