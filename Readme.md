
*ATTENTION this is totall WIP*

------------------------
Neos Splash Distribution
------------------------

Welcome to Neos!


This package shall help you setting up your new Neos project.

`composer create-project neos-splash`

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

