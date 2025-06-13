<?php

use FGTCLB\FileRequiredAttributes\Form\Element\FileRequiredValueElement;
use FGTCLB\FileRequiredAttributes\Hooks\FileReferenceRequiredFieldsHook;
use FGTCLB\FileRequiredAttributes\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton as Typo3LinkButton;

(static function (): void {
    // Datahandler Hooks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = FileReferenceRequiredFieldsHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1682603901292] = [
        'nodeName' => 'fileRequiredAttributeShow',
        'priority' => 40,
        'class' => FileRequiredValueElement::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Typo3LinkButton::class] = [
        'className' => LinkButton::class
    ];
})();
