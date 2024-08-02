<?php

use FGTCLB\FileRequiredAttributes\Backend\Form\Element\VirtualFieldElement;

(static function (): void {
    // Datahandler Hooks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \FGTCLB\FileRequiredAttributes\Hooks\FileReferenceRequiredFieldsHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1722264640125] = [
        'nodeName' => 'fileRequiredElement',
        'priority' => 50,
        'class' => VirtualFieldElement::class,
    ];
})();
