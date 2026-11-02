<?php

declare(strict_types=1);

use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use TYPO3\CMS\Core\Resource\FileType;

defined('TYPO3') or die();

(static function (): void {
    RequiredColumnsUtility::register(
        'copyright',
        [
            FileType::IMAGE->value,
        ]
    );
    // Registered twice with different file types, as two extensions could do.
    RequiredColumnsUtility::register(
        'right_of_use',
        [
            FileType::AUDIO->value,
        ]
    );
    RequiredColumnsUtility::register(
        'right_of_use',
        [
            FileType::VIDEO->value,
        ]
    );
})();
