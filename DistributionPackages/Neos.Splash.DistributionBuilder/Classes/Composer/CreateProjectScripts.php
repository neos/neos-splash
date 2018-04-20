<?php
namespace Neos\Splash\DistributionBuilder\Composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Neos\Flow\Cli\ConsoleOutput;

use Symfony\Component\Yaml\Yaml;

class CreateProjectScripts
{

    /**
     * Setup the neos distribution
     */
    static public function setupDistribution(Event $event)
    {
        $output = new ConsoleOutput();

        // project and vendor namespace
        $namespaceValidator = function($namespace) {
            if (preg_match('/^[A-Za-z0-9]+$/u', $namespace)) {
                return $namespace;
            }
            throw new \Exception(sprintf('Namespace "%s" is invalid', $namespace));
        };
        $vendor = $output->askAndValidate("Vendor-namespace: ", $namespaceValidator);
        $project = $output->askAndValidate("Project-name: ", $namespaceValidator);

//        // select site-package-template
//        $sitePackageConfigurations = Yaml::parse(__DIR__ . '/../../Resources/Private/SitePackageTemplates.yaml', false);
//        echo($sitePackageConfigurations);
//        return;
//
//        $sitePackageOptions = array_keys($sitePackageConfigurations);
//        $sitePackageIndex = $output->select('Please select the template for the site package', $sitePackageOptions, 'empty', false);
//        if ($sitePackageIndex) {
//            $sitePackageKey = $sitePackageOptions[$sitePackageIndex];
//        }
//
//        // select additional-packages
//        $additionalPackageConfigurations = Yaml::parse(__DIR__ . '/../../Resources/Private/AdditionalPackages.yaml');
//        $additionalPackageOptions = array_keys($additionalPackageConfigurations);
//        $additionalPackagesIndexes = $output->select('Please select additional packages', array_keys($additionalPackageConfigurations), null, true);
//        $additionalPackages = [];

        // show information
        $output->outputTable([
            ['VendorNamespace', $vendor],
            ['ProjectName', $project],
            ['SitePackage Template', $sitePackageKey]
        ]);

        $proceed = $output->askConfirmation('Is this correct?',  true);

        if (!$proceed) {
            die (1);
        }

        echo "setup";

        // create site package empty or a by template

        // adjust main composer.json

    }
}
