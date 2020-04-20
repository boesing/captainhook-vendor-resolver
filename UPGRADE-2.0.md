# Upgrade to 2.0

With v2.0, the vendor resolver only supports `captainhook/captainhook` ^5.0.

## Custom captainhook configuration location
With captainhook v5.0, Sebastian added custom `captainhook.json` location. If you are using this feature, make sure you create a `captainhook-vendor-resolver.json` in your project directory (next to `composer.json`) where you add a new property named `captainhook`.

**Example:**
```json
{
    "captainhook": "custom/directory/captainhook.json"
}
``` 

The `captainhook` path can be relative or absolute to your project directory.
