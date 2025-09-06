<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Resource;

/**
 * @todo typo3/cms-core:>=13.0 Remove the version switch and change behavior to
 *       FileType enum, as soon as TYPO3 12 support is dropped.
 */
abstract class FileType
{
    public const UNKNOWN = 0;
    public const TEXT = 1;
    public const IMAGE = 2;
    public const VIDEO = 3;
    public const AUDIO = 4;
    public const APPLICATION = 5;
}
