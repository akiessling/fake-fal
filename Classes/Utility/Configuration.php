<?php

declare(strict_types=1);

namespace Plan2net\FakeFal\Utility;

use Exception;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Configuration
 *
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class Configuration
{
    /**
     * Returns the whole extension configuration or a specific property
     *
     *
     * @return array|string|null
     */
    public static function getExtensionConfiguration(?string $key = null)
    {
        /** @var ExtensionConfiguration $helper */
        $helper = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        try {
            $configuration = $helper->get('fake_fal');
        } catch (Exception) {
            $configuration = [];
        }
        if (is_string($key)) {
            if (isset($configuration[$key])) {
                return (string) $configuration[$key];
            }

            return null;
        }

        return $configuration;
    }
}
