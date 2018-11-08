<?php
namespace Neos\Splash\DistributionBuilder\Service;

use Neos\Splash\DistributionBuilder\InstallerException;
use Neos\Splash\DistributionBuilder\Domain\ValueObjects\PackageRequirement;
use Neos\Utility\Arrays;
use Composer\Composer;
use Composer\Repository\RepositoryManager;
use Composer\Package\PackageInterface;
use Composer\Downloader\DownloadManager;

class PackageService
{

    /**
     * @param Composer $composer
     * @param string $path
     * @param PackageRequirement $packageDefinition
     * @throws InstallerException
     */
    public static function downloadPackageWithComposer(Composer $composer, string $path, PackageRequirement $packageDefinition)
    {
        /**
         * @var RepositoryManager $repositoryManager
         */
        $repositoryManager = $composer->getRepositoryManager();

        /**
         * PackageInterface
         */
        $package = $repositoryManager->findPackage($packageDefinition->getComposerName(), $packageDefinition->getVersion());
        if ($package) {
            /**
             * @var DownloadManager $downloadManager
             */
            $downloadManager = $composer->getDownloadManager();
            $downloadManager->download($package, $path, false);
        } else {
            throw new InstallerException(sprintf('package %s was not found or could not satisfy version constraint %s', $packageDefinition->getPackageKey(), $packageDefinition->getVersion()));
        }
    }

    /**
     * @param Composer $composer
     * @param string $path
     * @param PackageRequirement $oldPackageDefinition
     * @param PackageRequirement $newPackageDefinition
     * @throws \Exception
     */
    public static function alterPackageNamespace(Composer $composer, string $path, PackageRequirement $oldPackageDefinition, PackageRequirement $newPackageDefinition)
    {
        $replacements = [];

        $replacements[$oldPackageDefinition->getPackageKey()] = $newPackageDefinition->getPackageKey();
        $replacements[$oldPackageDefinition->getPhpNamespace()] = $newPackageDefinition->getPhpNamespace();
        $replacements[addslashes($oldPackageDefinition->getPhpNamespace())] = addslashes($newPackageDefinition->getPhpNamespace());

        StringReplacementService::replaceRecursively($replacements, $path);

        JsonFileService::modifyFile(
            $path . DIRECTORY_SEPARATOR . 'composer.json',
            [
                'name' => $newPackageDefinition->getComposerName(),
                'extra' => [
                    'neos' => [
                        'package-key' => $newPackageDefinition->getPackageKey()
                    ]
                ]
            ]
        );
    }
}
