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
   * Check if library is available.
   *
   * @return bool
   *   Return TRUE if the library is available, otherwise, FALSE.
   */
  public function isAvailable();

  /**
   * Return all available version(s).
   *
   * @return array
   *   Return an array with available versions of the library.
   */
  public function getVersions();

  /**
   * Return all available file(s).
   *
   * @param array $version
   *   Filter the returning array with this one.
   *
   * @return array
   *   Return an array with available files of the library.
   */
  public function getFiles(array $version = array());

  /**
   * Set the library to work with.
   *
   * @param string $library
   *   The library to work with.
   */
  public function setLibrary($library);

  /**
   * Get the library in use.
   *
   * @return string
   *   The library name.
   */
  public function getLibrary();

  /**
   * Set a particular URL.
   *
   * @param string $identifier
   *   The identifier.
   * @param string $url
   *   The URL.
   */
  public function setURL($identifier, $url);

  /**
   * Get a particular URL.
   *
   * @return string
   *   The URL.
   */
  public function getURL($identifier);

  /**
   * Set URLs.
   *
   * @param array $urls
   *   An array of URLs for querying the service.
   */
  public function setURLs(array $urls = array());

  /**
   * Get URLs.
   *
   * @return array
   *   Return an array of URLs in use for querying the service.
   */
  public function getURLs();

  /**
   * Make an HTTP Request.
   *
   * TODO: Do not use drupal_http_request.
   *
   * @param string $url
   *   The URL.
   * @param array $options
   *   The array of options passed to drupal_http_request.
   */
  public function request($url, array $options = array());

  /**
   * Get library information.
   *
   * @return array
   *   Return an array containing information about the library.
   */
  public function getInformation();

  /**
   * Get latest version available of a library.
   *
   * @return string
   *   The latest available version of the library.
   */
  public function getLatestVersion();

  /**
   * Perform a search for a library.
   *
   * @return array
   *   The resulting array.
   */
  public function search($library);

  /**
   * Check if a file is available locally.
   *
   * @param string $file
   *   The file to check.
   * @param string $version
   *   The version to check the file against.
   *
   * @return bool
   *   Return TRUE if the file is available, FALSE otherwise.
   */
  public function isLocalAvailable($file, $version);

  /**
   * Get the local file name of a library file.
   *
   * @param string $file
   *   The file to check.
   * @param string $version
   *   The version to check the file against.
   *
   * @return string
   *   Return the file name.
   */
  public function getLocalFileName($file, $version);

  /**
   * Get the local directory name of a library.
   *
   * @param string $version
   *   The version to check the file against.
   *
   * @return string
   *   Return the directory name.
   */
  public function getLocalDirectoryName($version = NULL);

  /**
   * Copy a library from the CDN to the local filesystem.
   *
   * @param array $versions
   *   The library versions to copy.
   */
  public function getLocalCopy(array $versions = array());

}
