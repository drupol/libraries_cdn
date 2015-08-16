# Libraries CDN API

Libraries CDN API is an API module to retrieve data from CDN like CDNJS or jsDelivr.

# API
```php
  // List available CDN plugins
  $plugins = \Drupal\libraries_cdn\LibrariesCDN::getAvailableCDN();

  // Check if a CDN plugin is available
  \Drupal\libraries_cdn\LibrariesCDN::isAvailableCDN($plugin_id);

  // Set the cdn you want to use
  \Drupal\libraries_cdn\LibrariesCDN::setPlugin('cdnjs');
  
  // Set the library you want to get data from
  \Drupal\libraries_cdn\LibrariesCDN::setLibrary('openlayers');
  
  // Check if the library is available
  \Drupal\libraries_cdn\LibrariesCDN::isAvailable();
  
  // Get information about the library
  \Drupal\libraries_cdn\LibrariesCDN::getInformation();

  // Get versions available
  \Drupal\libraries_cdn\LibrariesCDN::getVersions();
  
  // Get files available
  \Drupal\libraries_cdn\LibrariesCDN::getFiles();
  
  // Get latest version available
  \Drupal\libraries_cdn\LibrariesCDN::getLatestVersion();

  // Get array of CDN plugins ID where the library is available
  \Drupal\libraries_cdn\LibrariesCDN::find($library);

  // Perform a search on each CDN Plugins and return an array of results
  \Drupal\libraries_cdn\LibrariesCDN::search($library);
```
# Integration with Libraries API

This module provides a kind of autodiscovery for Libraries API through the ```hook_libraries_info_alter()```.
In order to have it working, add a new key to the library definition in ```hook_libraries_info()```.

Here's an example:

```
/*
 * Implementation of hook_libraries_info().
 */
function mymodule_libraries_info() {
  return array(
    'mylibrary' => array(
      'name' => 'MyLibrary library',
      'library path' => drupal_get_path('module', 'mymodule'),
      'version callback' => , // Set a callback here to get the version in use.
      'version arguments' => array(),
      'variants' => array(),
      'cdn' => array(
        'aliases' => array('mlib', 'mylib'),
        'options' => array(
          'weight' => -2,
          'group' => 'MyLib',
        ),
        'download' => array(
          'versions' => array('3.8.1'),
          'plugins' => array(
            'cdnjs' => array('latest'),
        )
      )
    )
  );
}
```

The explanation of this new key:
- plugins: array, the list of cdn plugins to search the library from. Will use all if not set.
- aliases: array, if the library has different names.
- options: array, this array will be applied to each file definition, see ```drupal_add_TYPE()``` (js or css) to see which are the keys.
- download: array, options to download a local copy of the library
  - versions: array, version to download on any CDN when available.
  - plugins: array, keys are CDN plugin ids. Values are versions to download when available. The special keyword: 'latest' can be used to download the latest version available.

# Extend the module

Create a simple drupal module.

Your module's info file must contains:

```
dependencies[] = registry_autoload
registry_autoload[] = PSR-4
```

Create directory structure that follows this one in your module:

```
src/Plugin/LibrariesCDN
```

Pay attention to the case, it's important.

Then, create your file containing your class in that directory.
Have a look at the files provided in the original module to inspire yours.

# TODO
* Do not use ```drupal_http_request```.
* More CDNs.
* More documentation.
* Better ```Libraries API``` integration.
* Permit the download and installation of libraries
