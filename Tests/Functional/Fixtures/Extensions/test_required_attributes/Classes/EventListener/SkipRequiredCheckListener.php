<?php

declare(strict_types=1);

namespace FGTCLB\TestRequiredAttributes\EventListener;

use FGTCLB\FileRequiredAttributes\Event\PostRequiredFieldCheckEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Lets a functional test switch the required check off for one reference by giving it the title
 * "skip-required-check", the way an integrator's listener would for references of its own.
 */
#[AsEventListener('test-required-attributes/skip-required-check')]
final class SkipRequiredCheckListener
{
    public function __invoke(PostRequiredFieldCheckEvent $event): void
    {
        if (($event->getData()['title'] ?? '') !== 'skip-required-check') {
            return;
        }
        $event->setRequiredColumns([]);
    }
}
