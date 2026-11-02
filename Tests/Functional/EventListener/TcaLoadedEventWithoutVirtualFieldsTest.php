<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\EventListener;

use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

final class TcaLoadedEventWithoutVirtualFieldsTest extends AbstractFunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'file_required_attributes' => [
                'virtualFields' => '0',
            ],
        ],
    ];

    #[Test]
    public function referenceOnlyShowsTheMetadataValue(): void
    {
        $columns = $GLOBALS['TCA']['sys_file_reference']['columns'];

        self::assertFalse($columns['virtual_copyright']['config']['parameters']['override']);
        self::assertArrayNotHasKey('copyright', $columns);
        self::assertStringNotContainsString(
            ',copyright',
            $GLOBALS['TCA']['sys_file_reference']['palettes']['imageoverlayPalette']['showitem']
        );
    }
}
