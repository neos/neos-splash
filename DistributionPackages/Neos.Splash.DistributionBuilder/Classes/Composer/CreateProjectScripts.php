<?php
namespace Neos\Splash\DistributionBuilder\Composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Composer;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Symfony\Component\Yaml\Yaml;

use Neos\Flow\Cli\ConsoleOutput;
use Neos\Utility\Files;
use Neos\Utility\Arrays;

use Neos\Splash\DistributionBuilder\Service\PackageService;
use Neos\Splash\DistributionBuilder\Domain\ValueObjects\PackageRequirement;

class CreateProjectScripts
{
    const LOCAL_SRC_PATH = 'DistributionPackages';

    /**
     * Setup the neos distribution
     */
    static public function setupDistribution(Event $event)
    {
        if (!defined('FLOW_PATH_ROOT')) {
            define('FLOW_PATH_ROOT', Files::getUnixStylePath(getcwd()) . '/');
        }

        $composer = $event->getComposer();
        $output = new ConsoleOutput();

        $output->outputLine();
        $output->outputLine('Welcome to Neos');
        $output->outputLine();
        $output->outputLine('Please answer some questions for finishing the setup of your Neos-distribution.');
        $output->outputLine();

        $configuration = self::getConfiguration();

        $availableSitePackages = $configuration['sitePackages']['items'] ?? [];
        $defaultSitePackage = $configuration['sitePackages']['default'] ?? null;

        $sitePackageChoices = array_map(function($item) { return $item['title'] . ': ' . $item['description']; }, $availableSitePackages);
        $sitePackageIndex = $output->select('Please select the template for your custom site-package', $sitePackageChoices, $defaultSitePackage, false);

        $sitePackageConfiguration = $availableSitePackages[$sitePackageIndex];

        // create or install site package
        $type = Arrays::getValueByPath($sitePackageConfiguration, 'type');

        $sitePackageKey = null;
        switch ($type) {
            case 'create':
                $sitePackageKey = self::askForPackageKey("Please define the namespace for your site-package:");
                self::createSitePackage($sitePackageConfiguration, $composer, $sitePackageKey);
                break;
            case 'clone':
                $sitePackageKey = self::askForPackageKey("Please define the namespace for your site-package:");
                self::cloneSitePackage($sitePackageConfiguration,  $composer, $sitePackageKey);
                break;
            case 'install':
                $sitePackageKey = $sitePackageConfiguration['options']['packageKey'] ?? null;
                self::installSitePackage($sitePackageConfiguration, $composer);
                break;
        }

        // remove splash
        $output->outputLine();
        $output->outputLine('Remove the setup tool neos/splash-distributionbuilder');
        shell_exec('composer remove neos/splash-distributionbuilder');
        Files::removeDirectoryRecursively(FLOW_PATH_ROOT . self::LOCAL_SRC_PATH . DIRECTORY_SEPARATOR . 'Neos.Splash.DistributionBuilder');

        // success
        $output->outputLine();
        $output->outputLine('Your distribution was prepared successfully.');
        $output->outputLine();
        $output->outputLine('For local development you still have to:');
        $output->outputLine();
        $output->outputLine('1. Add database credentials to Configuration/Development/Settings.yaml');
        $output->outputLine('2. Migrate database "./flow doctrine:migrate"');
        $output->outputLine('3. Import site data "./flow site:import --package-key ' . $sitePackageKey . ' "');
        $output->outputLine('4. Start the Webserver "./flow server:run"');

    }

    /**
     * @return array configuration
     */
    protected static function getConfiguration()
    {
        $rawConfiguration = file_get_contents(__DIR__ . '/../../Resources/Private/SitePackageTemplates.yaml');
        return Yaml::parse($rawConfiguration, true);
    }

    /**
     * @param string $title
     * @return string
     */
    protected static function askForPackageKey(string $title):string
    {
        $output = new ConsoleOutput();
        $output->outputLine();
        $output->outputLine($title);
        $output->outputLine();

        $vendorName = $output->ask("Vendor-namespace: ");
        $projectName = $output->ask("Project-name: ");

        $vendorName = trim($vendorName);
        $projectName = trim($projectName);

        return $vendorName . '.' . $projectName;
    }


    /**
     * @param array $sitePackageConfiguration
     * @param Composer $composer
     * @param string $newSitePackageKey
     * @throws \Neos\Splash\DistributionBuilder\InstallerException
     */
    protected static function createSitePackage(array $sitePackageConfiguration, Composer $composer, string $newSitePackageKey):void
    {
        $output = new ConsoleOutput();

        $newSitePackageDefinition = new PackageRequirement($newSitePackageKey, '@dev');
        $packagesPath = self::getPackagesPath();

        $output->outputLine(sprintf('Create package %s in path %s', $newSitePackageDefinition->getComposerName(), $packagesPath));

        $output->output(
            shell_exec(
                sprintf(
                    './flow package:create --package-key %s --package-type neos-site --packages-path %s',
                    $newSitePackageDefinition->getComposerName(),
                    $packagesPath
                )
            )
        );

        $output->outputLine(sprintf('Add composer requirement for package %s', $newSitePackageDefinition->getComposerName()));
        shell_exec(sprintf('composer require %s %s', $newSitePackageDefinition->getComposerName(), $newSitePackageDefinition->getVersion()));
    }

    /**
     * @param array $sitePackageConfiguration
     * @param Composer $composer
     * @param string $newSitePackageKey
     * @throws \Neos\Splash\DistributionBuilder\InstallerException
     */
    protected static function cloneSitePackage(array $sitePackageConfiguration, Composer $composer, string $newSitePackageKey):void
    {
        $output = new ConsoleOutput();


        $sitePackageKey = $sitePackageConfiguration['options']['packageKey'];
        $sitePackageVersion = $sitePackageConfiguration['options']['version'] ?? '*';
        $sitePackageDefinition = new PackageRequirement($sitePackageKey, $sitePackageVersion);

        $newSitePackageDefinition = new PackageRequirement($newSitePackageKey, '*@dev');
        $newSitePackagePath = self::getPackagesPath() . DIRECTORY_SEPARATOR . $newSitePackageDefinition->getPackageKey();

        $output->outputLine();
        $output->outputLine(
            sprintf(
                'The package template "%s" is downloaded and converted to package "%s" in path "%s"',
                $sitePackageDefinition->getPackageKey(),
                $newSitePackageDefinition->getPackageKey(),
                $newSitePackagePath
            )
        );

        PackageService::downloadPackageWithComposer($composer, $newSitePackagePath, $sitePackageDefinition);
        PackageService::alterPackageNamespace($composer, $newSitePackagePath, $sitePackageDefinition, $newSitePackageDefinition);

        $output->outputLine();
        $output->outputLine(sprintf('Add composer requirement for package %s', $newSitePackageDefinition->getComposerName()));
        shell_exec(sprintf('composer require %s %s', $newSitePackageDefinition->getComposerName(), $newSitePackageDefinition->getVersion()));
    }

    /**
     * @param array $sitePackageConfiguration
     * @param Composer $composer
     */
    protected static function installSitePackage(array $sitePackageConfiguration, Composer $composer):void
    {
        $sitePackageKey = $sitePackageConfiguration['options']['packageKey'];
        $sitePackageVersion = $sitePackageConfiguration['options']['version'] ?? '*';
        $sitePackageDefinition = new PackageRequirement($sitePackageKey, $sitePackageVersion);

        $output = new ConsoleOutput();

        $output->outputLine();
        $output->outputLine(sprintf('Add composer requirement for package %s', $sitePackageDefinition->getComposerName()));
        shell_exec(sprintf('composer require %s %s', $sitePackageDefinition->getComposerName(), $sitePackageDefinition->getVersion()));
    }

    /**
     * @return string
     */
    protected static function getPackagesPath():string
    {
        return FLOW_PATH_ROOT . self::LOCAL_SRC_PATH;
    }
}
