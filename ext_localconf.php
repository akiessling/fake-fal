<?php

defined('TYPO3') or die('Access denied');

(static function () {
    if ((bool)\Plan2net\FakeFal\Utility\Configuration::getExtensionConfiguration('enable')) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\ResourceFactory::class] = [
            'className' => \Plan2net\FakeFal\Resource\Core\ResourceFactory::class
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\ResourceStorage::class] = [
            'className' => \Plan2net\FakeFal\Resource\Core\ResourceStorage::class
        ];
    }
})();
