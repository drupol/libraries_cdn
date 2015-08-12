<?php
/**
 * @file
 * Contains LibrariesCDN.
 */

namespace Drupal\libraries_cdn;
use Drupal\libraries_cdn\Types\CDNBaseInterface;

/**
 * Class LibrariesCDN.
 */
class LibrariesCDN extends \Drupal {

  /* @var CDNBaseInterface $plugin */
  protected static $plugin;

  /**
   * Gets a list of available CDN plugins.
   *
   * @return array
   *   List of CDN plugins available.
   */
  public static function getAvailableCDN() {
    $options = array();
    $service_basename = 'libraries_cdn.LibrariesCDN';
    foreach (\Drupal::service($service_basename)->getDefinitions() as $service => $data) {
      $name = isset($data['label']) ? $data['label'] : $data['id'];
      $options[$data['id']] = $name;
    }
    asort($options);
    return $options;
  }

  /**
   * Return CDN Plugin id's of the CDN who provides a library.
   *
   * @param string $library
   *   The library to search.
   *
   * @return array $providers
   *   The array of providers who provides the searched library.
   */
  public static function find($library) {
    $providers = array();
    foreach(self::getAvailableCDN() as $cdn) {
      self::setPlugin($cdn, $library);
      if (self::isAvailable()) {
        $providers[] = $cdn;
      }
    }
    return $providers;
  }

  /**
   * Return CDN Plugin id's of the CDN who provides a library.
   *
   * @param string $library
   *   The library to search.
   *
   * @return array $providers
   *   The array of providers who provides the searched library.
   */
  public static function search($library) {
    $providers = array();
    foreach(self::getAvailableCDN() as $cdn) {
      self::setPlugin($cdn);
      $search = self::$plugin->search($library);
      if (!empty($search)) {
        $providers[$cdn] = $search;
      }
    }
    return $providers;
  }

  /**
   * Set the CDN plugin to use.
   *
   * @param $plugin
   * @param null $library
   */
  public static function setPlugin($plugin, $library = NULL) {
    /* @var CDNBaseInterface $plugin */
    $plugin = self::service('libraries_cdn.LibrariesCDN')->createInstance($plugin);
    if ($library) {
      $plugin->setLibrary($library);
    }
    self::$plugin = $plugin;
  }

  /**
   * Return the CDN Plugin object.
   *
   * @return \Drupal\libraries_cdn\Types\CDNBaseInterface
   *   The CDN Plugin object.
   */
  public static function getPlugin() {
    return self::$plugin;
  }

  /**
   * Set the library to work with.
   *
   * @param string $library
   */
  public static function setLibrary($library) {
    self::$plugin->setLibrary($library);
  }

  /**
   * Check if library is available
   *
   * @return bool
   */
  public static function isAvailable() {
    return self::$plugin->isAvailable();
  }

  /**
   * Return all available version(s).
   *
   * @return array
   */
  public static function getVersions() {
    return self::$plugin->getVersions();
  }

  /**
   * Return all available file(s).
   *
   * @return array
   */
  public static function getFiles() {
    return self::$plugin->getFiles();
  }

  /**
   * Get the library in use.
   *
   * @return string
   */
  public static function getLibrary() {
    return self::$plugin->getLibrary();
  }

  /**
   * Set a particular URL.
   *
   * @param string $identifier
   * @param string $url
   */
  public static function setURL($identifier, $url) {
    self::$plugin->setURL($identifier, $url);
  }

  /**
   * Get a particular URL.
   *
   * @return string
   */
  public static function getURL($identifier) {
    return self::$plugin->getURL($identifier);
  }

  /**
   * Set URLs.
   *
   * @param array $urls
   */
  public static function setURLs(array $urls) {
    self::$plugin->setURLs($urls);
  }

  /**
   * Get URLs.
   *
   * @return array
   */
  public static function getURLs() {
    return self::$plugin->getURLs();
  }

  /**
   * Get library information.
   *
   * @return array
   */
  public static function getInformation() {
    return self::$plugin->getInformation();
  }

  /**
   * Get latest version available of a library.
   *
   * @return string
   */
  public static function getLatestVersion() {
    return self::$plugin->getLatestVersion();
  }

  /**
   * Generate an array for the variants of the Libraries API module.
   *
   * @param array $options
   *   Array to apply to each file.
   * @return array
   *   The returned array can be applied to the 'variants' key in the library
   *   definition in hook_libraries_info().
   */
  public static function getLibrariesVariants(array $options = array()) {
    $variants = array();
    $module_path = drupal_get_path('module', 'libraries_cdn');
    $information = self::getInformation();

    $name = isset($information['name']) ? $information['name'] : self::getLibrary();

    foreach (self::getFiles() as $version => $files) {
      $variant = self::$plugin->getPluginId() . ':' . $version;
      foreach($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (strpos($file, 'debug') !== FALSE || strpos($file, 'min') !== FALSE) {
          continue;
        }

        $variants[$variant]['name'] = sprintf("%s %s", $name, $version);
        $variants[$variant]['library path'] = $module_path;
        $variants[$variant]['files'][$ext][$file] = array(
          'type' => 'external',
          'data' => $file,
        ) + $options;
      };
    }
    return $variants;
  }

}
