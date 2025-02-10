<?php

use FGTCLB\FileRequiredAttributes\Resource\FileType;
use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;

(static function (): void {
    RequiredColumnsUtility::register(
        'copyright',
        [
            FileType::IMAGE,
        ]
    );
    RequiredColumnsUtility::register(
        'alternative',
        [
            FileType::IMAGE,
        ]
    );
})();
