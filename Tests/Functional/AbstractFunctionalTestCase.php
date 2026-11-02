<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Base for every functional test of the extension. The fixture extension registers "copyright" as
 * required for images and the radio "right_of_use" for audio and video files, which is what the
 * tests exercise.
 */
abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-backend',
        'typo3/cms-filelist',
        'typo3/cms-filemetadata',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/file-required-attributes',
        'fgtclb/test-required-attributes',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'file_required_attributes' => [
                'virtualFields' => '1',
            ],
        ],
    ];
}
