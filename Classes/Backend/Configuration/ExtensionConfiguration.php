<?php

declare(strict_types=1);

namespace FGTCLB\FileRequiredAttributes\Backend\Configuration;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\ViewHelpers\Form\TypoScriptConstantsViewHelper;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ExtensionConfiguration
{
    /**
     * @param array{fieldName: string, fieldValue: string, propertyName: string} $field
     * @param TypoScriptConstantsViewHelper $pObj
     * @return string
     */
    public function render(array $field, TypoScriptConstantsViewHelper $pObj): string
    {
        // TODO load and include TCA for sys_file_metadata
        ExtensionManagementUtility::loadBaseTca();
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplate('Configuration');
        //$columns = array_keys($GLOBALS['TCA']['sys_file_metadata']['columns']);
        $assignedValues = [
            'field' => $field,
            'columns' => $columns ?? [],
        ];
        $view->assignMultiple($assignedValues);
        return $view->render();
    }
}
