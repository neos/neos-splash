
*ATTENTION this is totall WIP*

------------------------
Neos Splash Distribution
------------------------

Welcome to Neos!


This package shall help you setting up your new Neos project.

`composer create-project neos-splash`

Configuration
-------------

```yaml

#
# the site packages that are offered during tge setup
#
sitePackages:

  # 
  # the package that is enabled by default
  # 
  default: "empty"

  #  
  # each item here has to provide the keys title, description
  # and type and options. The option keys depend on the 
  # type
  # 
  items:
  
    # type:create will create a new empty package
    # in the given namespace 
    empty:
      title: 'Empty'
      description: 'Empty site-package.'
      type: 'create'

    #
    # type:clone will clone an existing package into
    # the given namespace
    # 
    cloneNeosDemo:
      title: 'Neos.Demo'
      description: 'The classic neos-demo site-package.'
      type: 'clone'
      options:
        packageKey: 'neos/demo'
        version: '^4.1'

    #
    # type:install will install the slected site package 
    # as a composer dependency
    #
    installNeosDemo:
      title: 'Neos.Demo'
      description: 'The classic neos-demo site-package.'
      type: 'Install'
      options:
        packageKey: 'neos/demo'
        version: '^4.1'
```


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

