<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\EventListener;

use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileType;

/**
 * The fixture extension registers "copyright" as required for images and "right_of_use" for audio and
 * video files, with virtual fields enabled.
 */
final class TcaLoadedEventTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function requiredColumnIsMarkedRequiredInTheMetadataFormOfItsFileType(): void
    {
        $types = $GLOBALS['TCA']['sys_file_metadata']['types'];

        self::assertTrue($types[FileType::IMAGE->value]['columnsOverrides']['copyright']['config']['required'] ?? false);
        self::assertArrayNotHasKey('copyright', $types[FileType::APPLICATION->value]['columnsOverrides'] ?? []);
    }

    #[Test]
    public function requiredColumnsAreListedPerFileTypeInTheMetadataCtrl(): void
    {
        self::assertSame(
            [
                FileType::IMAGE->value => [
                    'copyright',
                ],
                FileType::AUDIO->value => [
                    'right_of_use',
                ],
                FileType::VIDEO->value => [
                    'right_of_use',
                ],
            ],
            $GLOBALS['TCA']['sys_file_metadata']['ctrl']['required_attributes']
        );
    }

    #[Test]
    public function referenceShowsTheMetadataValueInAVirtualColumn(): void
    {
        $column = $GLOBALS['TCA']['sys_file_reference']['columns']['virtual_copyright'];

        self::assertSame('user', $column['config']['type']);
        self::assertSame('fileRequiredAttributeShow', $column['config']['renderType']);
        self::assertSame('copyright', $column['config']['parameters']['originalField']);
        self::assertTrue($column['config']['parameters']['override']);
    }

    #[Test]
    public function referenceGetsAColumnOverridingTheMetadataValue(): void
    {
        $config = $GLOBALS['TCA']['sys_file_reference']['columns']['copyright']['config'];

        self::assertSame('useOrOverridePlaceholder', $config['mode']);
        self::assertSame('__row|uid_local|metadata|copyright', $config['placeholder']);
        self::assertTrue($config['nullable']);
    }

    #[Test]
    public function bothColumnsAreAddedToTheImagePaletteOnly(): void
    {
        $palettes = $GLOBALS['TCA']['sys_file_reference']['palettes'];

        self::assertStringContainsString(',virtual_copyright,copyright,--linebreak--', $palettes['imageoverlayPalette']['showitem']);
        self::assertStringNotContainsString('copyright', $palettes['basicoverlayPalette']['showitem']);
        self::assertStringNotContainsString('copyright', $palettes['videoOverlayPalette']['showitem']);
        self::assertStringNotContainsString('copyright', $palettes['audioOverlayPalette']['showitem']);
    }
}
