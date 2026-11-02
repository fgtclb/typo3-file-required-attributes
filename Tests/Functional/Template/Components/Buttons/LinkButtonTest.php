<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\Template\Components\Buttons;

use FGTCLB\FileRequiredAttributes\Template\Components\Buttons\LinkButton;
use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton as CoreLinkButton;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class LinkButtonTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function coreLinkButtonIsReplaced(): void
    {
        self::assertInstanceOf(LinkButton::class, GeneralUtility::makeInstance(CoreLinkButton::class));
    }

    #[Test]
    public function highlightedButtonIsRenderedAsDanger(): void
    {
        $button = GeneralUtility::makeInstance(CoreLinkButton::class)
            ->setHref('/typo3/record/edit')
            ->setTitle('Edit metadata')
            ->setIcon($this->get(IconFactory::class)->getIcon('actions-open', IconSize::SMALL))
            ->setClasses('required-attributes-missing');

        $html = $button->render();

        self::assertStringContainsString('btn-danger', $html);
        self::assertStringNotContainsString('btn-default', $html);
        self::assertStringNotContainsString('required-attributes-missing', $html);
    }

    #[Test]
    public function buttonWithoutHighlightIsRenderedUnchanged(): void
    {
        $button = GeneralUtility::makeInstance(CoreLinkButton::class)
            ->setHref('/typo3/record/edit')
            ->setTitle('Edit metadata')
            ->setIcon($this->get(IconFactory::class)->getIcon('actions-open', IconSize::SMALL));

        $html = $button->render();

        self::assertStringContainsString('btn-default', $html);
        self::assertStringNotContainsString('btn-danger', $html);
    }

    #[Test]
    #[DataProvider('validityDataProvider')]
    public function buttonIsValidOnlyWithHrefTitleAndIcon(string $href, string $title, bool $withIcon, bool $expected): void
    {
        $button = GeneralUtility::makeInstance(CoreLinkButton::class)
            ->setHref($href)
            ->setTitle($title);
        if ($withIcon) {
            $button->setIcon($this->get(IconFactory::class)->getIcon('actions-open', IconSize::SMALL));
        }

        self::assertSame($expected, $button->isValid());
    }

    public static function validityDataProvider(): \Generator
    {
        yield 'complete button is valid' => [
            'href' => '/typo3/record/edit',
            'title' => 'Edit metadata',
            'withIcon' => true,
            'expected' => true,
        ];
        yield 'button without href is invalid' => [
            'href' => ' ',
            'title' => 'Edit metadata',
            'withIcon' => true,
            'expected' => false,
        ];
        yield 'button without title is invalid' => [
            'href' => '/typo3/record/edit',
            'title' => '',
            'withIcon' => true,
            'expected' => false,
        ];
        yield 'button without icon is invalid' => [
            'href' => '/typo3/record/edit',
            'title' => 'Edit metadata',
            'withIcon' => false,
            'expected' => false,
        ];
    }
}
