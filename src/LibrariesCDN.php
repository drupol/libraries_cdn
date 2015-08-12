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
   * Set the CDN plugin to use.
   *
   * @param $plugin
   * @param null $library
   */
  public static function setPlugin($plugin, $library = NULL) {
    $plugin = self::service('libraries_cdn.LibrariesCDN')->createInstance($plugin);
    if ($library) {
      self::$plugin->setLibrary($library);
    }
    self::$plugin = $plugin;
  }

  /**
   * Set the library to work with.
   *
   * @param $library
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
   * @param $identifier
   * @param $url
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

}