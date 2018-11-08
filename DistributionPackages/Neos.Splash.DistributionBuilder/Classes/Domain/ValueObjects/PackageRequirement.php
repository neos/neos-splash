<?php
namespace Neos\Splash\DistributionBuilder\Domain\ValueObjects;

class PackageRequirement
{

    protected $vendor;

    protected $name;

    protected $version;

    public function __construct(string $packageKey, string $version)
    {
        list($vendor, $name) = explode('.', $packageKey, 2);
        $this->vendor = $vendor;
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPackageKey()
    {
        return $this->vendor . '.' . $this->name;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getComposerName()
    {
        return strtolower($this->vendor) . '/' . strtolower(str_replace('.', '-', $this->name));
    }

    /**
     * @return string
     */
    public function getPhpNamespace()
    {
        return str_replace('.', '\\', $this->getPackageKey());
    }
}
