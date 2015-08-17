<?php
/**
 * @file
 * Class CDNBase.
 */

namespace Drupal\libraries_cdn\Types;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class CDNBase.
 */
abstract class CDNBase extends PluginBase implements CDNBaseInterface {
  /**
   * This flag is set to true when the library is available.
   */
  protected $available;

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration = array()) {
    $this->configuration = $configuration;
    $this->available = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setLibrary($library) {
    $this->configuration['library'] = $library;
    $this->available = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return $this->configuration['library'];
  }

  /**
   * {@inheritdoc}
   */
  public function setURL($identifier, $url) {
    $this->configuration['urls'][$identifier] = $url;
    $this->available = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getURL($identifier) {
    return $this->configuration['urls'][$identifier];
  }

  /**
   * {@inheritdoc}
   */
  public function setURLs(array $urls = array()) {
    $this->configuration['urls'] = $urls;
    $this->available = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getURLs() {
    return $this->configuration['urls'];
  }

  /**
   * {@inheritdoc}
   */
  public function request($url, array $options = array()) {
    return (array) drupal_http_request($url, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function query($url, array $options = array()) {
    $request = $this->request(sprintf($url, $this->getLibrary()), $options);
    if ($request['code'] != 200) {
      return array();
    }
    return json_decode($request['data'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestVersion() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function search($library) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return $this->available;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles(array $version = array()) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getInformation() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function convertFiles(array $files, $version) {
    $results = array();
    foreach ($files as $file) {
      $results[] = sprintf($this->getURL(__FUNCTION__), $this->getLibrary(), $version) . $file;
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocalAvailable($file, $version) {
    return file_exists($this->getLocalFileName(basename($file), $version));
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalFileName($file, $version) {
    return $this->getLocalDirectoryName($version) . '/' . basename($file);
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalDirectoryName($version = NULL) {
    return implode(
      '/',
      array(
        'public:/',
        'libraries',
        $this->getPluginId(),
        $this->getLibrary(),
        $version,
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalCopy(array $versions = array()) {
    $assets = $this->getFiles();

    if (!empty($versions)) {
      $assets = array_intersect_key($assets, array_combine($versions, $versions));
    }

    foreach ($assets as $version => $files) {
      foreach ($files as $file) {
        if (!$this->isLocalAvailable($file, $version)) {
          $directory = $this->getLocalDirectoryName($version);
          file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
          $data = drupal_http_request('http:' . $file);
          file_unmanaged_save_data($data->data, $this->getLocalFileName($file, $version), FILE_EXISTS_REPLACE);
        }
      }
    }
  }

}
