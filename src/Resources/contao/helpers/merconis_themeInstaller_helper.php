<?php
namespace Merconis\ThemeInstaller;

use Composer\InstalledVersions;

class merconis_themeInstaller_helper
{
    public static function getInstalledThemeExtensions()
    {
        $arr_installedThemeExtensions = [];

        foreach (InstalledVersions::getInstalledPackages() as $strPackage)
        {
            if (str_contains($strPackage, '/merconis-theme-installer')) {
                continue;
            }

            if (str_contains($strPackage, '/merconis-theme')) {
                $arr_installedThemeExtensions[] = $strPackage;
            }
        }

        return $arr_installedThemeExtensions;
    }
}
