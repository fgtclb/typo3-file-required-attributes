<?php

(static function (): void {
    // Datahandler Hooks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \FGTCLB\FileRequiredAttributes\Hooks\SysFileReferenceCopyrightChangeHook::class;
})();
