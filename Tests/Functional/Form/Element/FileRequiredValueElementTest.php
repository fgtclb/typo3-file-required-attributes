<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Tests\Functional\Form\Element;

use FGTCLB\FileRequiredAttributes\Tests\Functional\AbstractFunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

final class FileRequiredValueElementTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/DataSet/Files.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/References.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)
            ->createFromUserPreferences($this->setUpBackendUser(1));
    }

    #[Test]
    public function metadataValueOfTheReferencedFileIsShown(): void
    {
        $result = $this->get(NodeFactory::class)->create([
            'renderType' => 'fileRequiredAttributeShow',
            'tableName' => 'sys_file_reference',
            'fieldName' => 'virtual_copyright',
            'databaseRow' => [
                'uid' => 2,
            ],
            'parameterArray' => [
                'fieldConf' => $GLOBALS['TCA']['sys_file_reference']['columns']['virtual_copyright'],
            ],
        ])->render();

        self::assertStringContainsString('<p>Jane Doe</p>', $result['html']);
    }

    #[Test]
    public function missingMetadataValueIsShownAsEmpty(): void
    {
        $result = $this->get(NodeFactory::class)->create([
            'renderType' => 'fileRequiredAttributeShow',
            'tableName' => 'sys_file_reference',
            'fieldName' => 'virtual_copyright',
            'databaseRow' => [
                'uid' => 1,
            ],
            'parameterArray' => [
                'fieldConf' => $GLOBALS['TCA']['sys_file_reference']['columns']['virtual_copyright'],
            ],
        ])->render();

        self::assertStringContainsString('<p>[empty]</p>', $result['html']);
    }

    #[Test]
    public function metadataValueIsEscaped(): void
    {
        $result = $this->get(NodeFactory::class)->create([
            'renderType' => 'fileRequiredAttributeShow',
            'tableName' => 'sys_file_reference',
            'fieldName' => 'virtual_copyright',
            'databaseRow' => [
                'uid' => 3,
            ],
            'parameterArray' => [
                'fieldConf' => $GLOBALS['TCA']['sys_file_reference']['columns']['virtual_copyright'],
            ],
        ])->render();

        self::assertStringNotContainsString('<script>', $result['html']);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $result['html']);
    }

    #[Test]
    public function radioValueIsShownWithItsLabel(): void
    {
        $result = $this->get(NodeFactory::class)->create([
            'renderType' => 'fileRequiredAttributeShow',
            'tableName' => 'sys_file_reference',
            'fieldName' => 'virtual_right_of_use',
            'databaseRow' => [
                'uid' => 3,
            ],
            'parameterArray' => [
                'fieldConf' => [
                    'label' => 'Usage rights',
                    'config' => [
                        'type' => 'user',
                        'renderType' => 'fileRequiredAttributeShow',
                        'parameters' => [
                            'originalField' => 'right_of_use',
                            'override' => false,
                        ],
                    ],
                ],
            ],
        ])->render();

        self::assertStringContainsString('<p>Limited usage rights</p>', $result['html']);
    }

    #[Test]
    public function referenceNotYetStoredRendersNothing(): void
    {
        $result = $this->get(NodeFactory::class)->create([
            'renderType' => 'fileRequiredAttributeShow',
            'tableName' => 'sys_file_reference',
            'fieldName' => 'virtual_copyright',
            'databaseRow' => [
                'uid' => 'NEW1234',
            ],
            'parameterArray' => [
                'fieldConf' => $GLOBALS['TCA']['sys_file_reference']['columns']['virtual_copyright'],
            ],
        ])->render();

        self::assertSame('', $result['html']);
    }
}
