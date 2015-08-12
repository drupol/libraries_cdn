# Libraries CDN API

Libraries CDN API is an API module to retrieve data from CDN like CDNJS or jsDelivr.

# API
```php
  // List available CDN plugins
  $plugins = \Drupal\libraries_cdn\LibrariesCDN::getAvailableCDN();

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
```

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
