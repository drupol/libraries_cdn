<?php
/**
 * @file
 * Interface CDNBaseInterface.
 */

namespace Drupal\libraries_cdn\Types;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface CDNBaseInterface.
 */
interface CDNBaseInterface extends PluginInspectionInterface {
  /**
   * Check if library is available
   *
   * @return bool
   */
  public function isAvailable();

  /**
   * Return all available version(s).
   *
   * @return array
   */
  public function getVersions();

  /**
   * Return all available file(s).
   *
   * @return array
   */
  public function getFiles(array $version = array());

  /**
   * Set the library to work with.
   *
   * @param $library
   */
  public function setLibrary($library);

  /**
   * Get the library in use.
   */
  public function getLibrary();

  /**
   * Set a particular URL.
   *
   * @param $identifier
   * @param $url
   */
  public function setURL($identifier, $url);

  /**
   * Get a particular URL.
   */
  public function getURL($identifier);

  /**
   * Set URLs.
   *
   * @param array $urls
   */
  public function setURLs(array $urls = array());

  /**
   * Get URLs.
   */
  public function getURLs();

  /**
   * Make an HTTP Request.
   *
   * TODO: Do not use drupal_http_request.
   *
   * @param string $url
   * @param array $options
   */
  public function request($url, array $options = array());

  /**
   * Get library information.
   *
   * @return array
   */
  public function getInformation();

  /**
   * Get latest version available of a library.
   *
   * @return string
   */
  public function getLatestVersion();
}
