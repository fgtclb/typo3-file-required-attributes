<?php

(static function (): void {
    // Datahandler Hooks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \FGTCLB\FileRequiredAttributes\Hooks\FileReferenceRequiredFieldsHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1682603901292] = [
        'nodeName' => 'fileRequiredAttributeShow',
        'priority' => 40,
        'class' => \FGTCLB\FileRequiredAttributes\Form\Element\FileRequiredValueElement::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton::class] = [
        'className' => WebVision\FileRequiredAttributes\Template\Components\Buttons\LinkButton::class
    ];
})();
