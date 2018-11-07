<?php
namespace Neos\Splash\DistributionBuilder\Composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Symfony\Component\Yaml\Yaml;

use Neos\Flow\Cli\ConsoleOutput;
use Neos\Utility\Files;

use Neos\Splash\DistributionBuilder\Service\PackageService;


class CreateProjectScripts
{
    const LOCAL_SRC_PATH = 'src';

    /**
     * Setup the neos distribution
     */
    static public function setupDistribution(Event $event)
    {
        if (!defined('FLOW_PATH_ROOT')) {
            define('FLOW_PATH_ROOT', Files::getUnixStylePath(getcwd()) . '/');
        }

        /**
         * @var Composer
         */
        $composer = $event->getComposer();

        $output = new ConsoleOutput();
        $output->outputLine();

        // site package template
        $output->outputLine();
        $sitePackageConfigurations = Yaml::parse(
            file_get_contents(__DIR__ . '/../../Resources/Private/SitePackageTemplates.yaml' ),
            true
        );

        $sitePackageChoices = array_map(function($item) { return $item['title'] . ': ' . $item['description']; }, $sitePackageConfigurations);
        $sitePackageChoice = $output->select('Please select the template for your custom site-package', $sitePackageChoices, 0, false);
        $sitePackageIndex = array_search($sitePackageChoice, $sitePackageChoices);

        $sitePackage = $sitePackageConfigurations[$sitePackageIndex];
        $sitePackageName = $sitePackage['composerName'] ?? null;
        $sitePackageVersion = $sitePackage['composerConstraint'] ?? '*';

        // project and vendor namespace
        $output->outputLine();
        $output->outputLine("Please define the namespace for your site-package:");
        $output->outputLine();
        $namespaceValidator = function($namespace) {
            if (preg_match('/^[A-Za-z0-9]+$/u', $namespace)) {
                return $namespace;
            }
            throw new \Exception(sprintf('Namespace "%s" is invalid', $namespace));
        };
        $vendorName = $output->ask("Vendor-namespace: ");
        $projectName = $output->ask("Project-name: ");

        if ($sitePackageName) {

            $customSitePackageKey = $vendorName . '.' . $projectName;
            $customSitePackageComposerName = strtolower($vendorName) . '/' . strtolower(str_replace('.', '-', $projectName));
            $customSitePackageNamespace = str_replace('.', '\\', $customSitePackageKey);
            $customSitePackagePath = FLOW_PATH_ROOT . self::LOCAL_SRC_PATH . DIRECTORY_SEPARATOR . $customSitePackageKey;

            $output->outputLine();
            $output->outputLine(sprintf('The package template "%s" is downloaded and converted to package "%s" in path "%s"' , $sitePackageName,  $customSitePackageKey, $customSitePackagePath));

            PackageService::downloadPackageWithComposer(
                $composer,
                $sitePackageName,
                $sitePackageVersion,
                $customSitePackagePath
            );

            PackageService::alterPackageNamespace(
                $customSitePackagePath,
                $customSitePackageComposerName,
                $customSitePackageKey,
                $customSitePackageNamespace
            );

            // require site package
            $output->outputLine();
            $output->outputLine(sprintf('Add composer requirement for package %s' ,  $customSitePackageComposerName));
            shell_exec( sprintf('composer require %s 1.0.0', $customSitePackageComposerName));

            // remove dependency to splash distribution builder
            $output->outputLine();
            $output->outputLine('Remove dependency to neos/splash-distributionbuilder');
            shell_exec( 'composer remove neos/splash-distributionbuilder');
            Files::removeDirectoryRecursively(FLOW_PATH_ROOT . self::LOCAL_SRC_PATH . DIRECTORY_SEPARATOR . 'Neos.Splash.DistributionBuilder');
            $output->outputLine();
        } else {
            $output->outputLine();
            $output->outputLine('Sorry empty site-packages cannot be created right now.');
            $output->outputLine('The package create command does not support to create them in local src folder yet.');
            $output->outputLine();
            die(1);
        }

        // success
        $output->outputLine();
        $output->outputLine('Your distribution was prepared successfully.');
        $output->outputLine();
        $output->outputLine('For local development you still have to:');
        $output->outputLine();
        $output->outputLine('1. Add database credentials to Configuration/Development/Settings.yaml');
        $output->outputLine('2. Migrate database "./flow doctrine:migrate"');
        $output->outputLine('3. Import site data "./flow site:import --package-key ' . $vendorName . '.' . $projectName . ' "');
        $output->outputLine('4. Start the Webserver "./flow server:run"');

    }
}
