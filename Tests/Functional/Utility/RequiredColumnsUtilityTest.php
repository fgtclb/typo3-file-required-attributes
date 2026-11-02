<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\Utility;

use FGTCLB\FileRequiredAttributes\Exception\ColumnNotRegisteredException;
use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use FGTCLB\FileRequiredAttributes\Utility\RequiredColumnsUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileType;

final class RequiredColumnsUtilityTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function registeringAColumnUnknownToTheMetadataTcaIsRejected(): void
    {
        $this->expectException(ColumnNotRegisteredException::class);
        $this->expectExceptionCode(1681395576121);

        RequiredColumnsUtility::register('column_that_does_not_exist', [FileType::IMAGE->value]);
    }

    #[Test]
    public function registeringAnAlreadyRegisteredColumnAgainKeepsASingleRegistration(): void
    {
        RequiredColumnsUtility::register('copyright', [FileType::IMAGE->value]);

        $registrations = array_filter(
            RequiredColumnsUtility::getRequiredColumns(),
            static fn(array $registration): bool => $registration['columnName'] === 'copyright'
        );
        self::assertCount(1, $registrations);
    }

    #[Test]
    public function registeringAColumnAgainWithOtherFileTypesRequiresItForAllOfThem(): void
    {
        $registrations = array_values(array_filter(
            RequiredColumnsUtility::getRequiredColumns(),
            static fn(array $registration): bool => $registration['columnName'] === 'right_of_use'
        ));

        self::assertSame(
            [
                [
                    'columnName' => 'right_of_use',
                    'fileTypes' => [
                        FileType::AUDIO->value,
                        FileType::VIDEO->value,
                    ],
                ],
            ],
            $registrations
        );
    }

    #[Test]
    #[DataProvider('missingValueDataProvider')]
    public function valueIsMissingWhenEmptyOrNotSet(string $columnName, mixed $value, bool $expectedMissing): void
    {
        self::assertSame($expectedMissing, RequiredColumnsUtility::isValueMissing($columnName, $value));
    }

    public static function missingValueDataProvider(): \Generator
    {
        yield 'null text' => [
            'columnName' => 'copyright',
            'value' => null,
            'expectedMissing' => true,
        ];
        yield 'empty text' => [
            'columnName' => 'copyright',
            'value' => '',
            'expectedMissing' => true,
        ];
        yield 'whitespace-only text' => [
            'columnName' => 'copyright',
            'value' => " \t ",
            'expectedMissing' => true,
        ];
        yield 'text 0' => [
            'columnName' => 'copyright',
            'value' => '0',
            'expectedMissing' => false,
        ];
        yield 'text' => [
            'columnName' => 'copyright',
            'value' => 'Jane Doe',
            'expectedMissing' => false,
        ];
        yield 'radio on its not set item 0' => [
            'columnName' => 'right_of_use',
            'value' => 0,
            'expectedMissing' => true,
        ];
        yield 'radio on its not set item given as string' => [
            'columnName' => 'right_of_use',
            'value' => '0',
            'expectedMissing' => true,
        ];
        yield 'radio on an item' => [
            'columnName' => 'right_of_use',
            'value' => 1,
            'expectedMissing' => false,
        ];
        yield 'array' => [
            'columnName' => 'copyright',
            'value' => ['Jane Doe'],
            'expectedMissing' => true,
        ];
    }

    #[Test]
    public function requiredColumnsAreGroupedByFileType(): void
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
            RequiredColumnsUtility::getRequiredColumnsFromTCA()
        );
    }
}
