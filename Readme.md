
*ATTENTION this is totall WIP*

------------------------
Neos Splash Distribution
------------------------

Welcome to Neos!


This package shall help you setting up your new Neos project.

`composer create-project neos-splash`

Configuration
-------------

The offered options are configured in the file DistributionPackages/Neos.Splash.DistributionBuilder/Configuration/Splash.yaml

```yaml

#
# the site packages that are offered during the setup process
#
sitePackages:

  # 
  # the package that is enabled by default
  # 
  default: "createEmpty"

  #  
  # each item here has to provide the keys title, description
  # and type and options. The option keys depend on the 
  # type
  # 
  items:
  
    #
    # type:create will create a new empty package
    # in the given namespace
    #
    createEmpty:
      title: 'Empty'
      description: 'Empty site-package.'
      type: 'create'

    #
    # type:clone will clone an existing package into
    # the given namespace
    #
    cloneNeosDemo:
      title: 'Neos.Demo'
      description: 'the classic demo package, cloned into your namespace for local adjustments'
      type: 'clone'
      options:
        packageKey: 'Neos.Demo'
        version: '^4.1'

    #
    # type:install will install the slected site package
    # as a composer dependency
    #
    installNeosDemo:
      title: 'Neos.Demo'
      description: 'the classic demo package, installed as external dependency'
      type: 'Install'
      options:
        packageKey: 'Neos.Demo'
        version: '^4.1'
```

Custom configuration
--------------------

The environment variable `SPLASH_CONFIGURATION` can be used to pass a custom configuration file to the splash installer.

Use a custom local file: `SPLASH_CONFIGURATION=~/mySplashConfiguration.yaml composer create-project neos-splash`  
You can even use remote files: `SPLASH_CONFIGURATION=http://example.org composer create-project neos-splash`  

Development
-----------

During development you will have to add your local repository to the global composer configuration.

Add this to your global composer configuration config.json:

```yaml
{
    "repositories": {
        "neos-splash": {
            "type": "path",
            "url": "__path_to_neos_splash__",
            "options": {
                "symlink": false
            }
        }
    }
}
```

And the use `create-project` to setup you neos-distribution:

`composer create-project neos/splash my_custom_project --stability dev`

