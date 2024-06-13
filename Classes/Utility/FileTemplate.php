<?php
declare(strict_types=1);

namespace Plan2net\FakeFal\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileTemplate
{
    public static function getTemplateFilePath(string $fileExtension)
    {
        $templateConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fake_fal']['fileTemplates'] ?? [];
        $fileExtension = strtoupper($fileExtension);

        if (isset($templateConfiguration[$fileExtension])) {
            $path = GeneralUtility::getFileAbsFileName($templateConfiguration[$fileExtension]);
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }
}