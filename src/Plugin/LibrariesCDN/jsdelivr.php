<?php
/**
 * @file
 * Component: jsDelivr.
 */

namespace Drupal\libraries_cdn\Plugin\LibrariesCDN;

use Drupal\Component\Plugin\PluginBase;
use Drupal\libraries_cdn\Component\Annotation\LibrariesCDNPlugin;
use Drupal\libraries_cdn\Types\CDNBase;

/**
 * Class jsDelivr.
 *
 * @LibrariesCDNPlugin(
 *  id = "jsdelivr",
 *  description = "jsDelivr Integration"
 * )
 */
class jsDelivr extends CDNBase {
  /**
   * This flag is set to true when the library is available.
   */
  protected $available;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['urls'])) {
      $configuration['urls'] = array();
    }
    $configuration['urls'] += array(
      'isAvailable' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries/%s',
      'getInformation' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=name,mainfile,lastversion,description,homepage,github,author',
      'getVersions' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=versions',
      'getFiles' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=assets',
      'search' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=*%s*',
      'convertFiles' => '//cdn.jsdelivr.net/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    if (isset($this->available)) {
      return $this->available;
    }

    $data = $this->request($this->getURL(__FUNCTION__));

    if (isset($data[0])) {
      $this->available = TRUE;
      return TRUE;
    } else {
      $this->available = FALSE;
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    return $data[0]['versions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles(array $versions = array()) {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    $results = array();
    foreach ((array) $data[0]['assets'] as $asset) {
      $results[$asset['version']] = $this->convertFiles($asset['files'], $asset['version']);
    }

    return empty($versions) ? $results : array_intersect_key($results, array_combine(array_values($versions), array_values($versions)));
  }

  /**
   * {@inheritdoc}
   */
  public function convertFiles(array $files = array(), $version) {
    $results = array();
    foreach ($files as $file) {
      $results[] = sprintf($this->getURL(__FUNCTION__), $this->getLibrary(), $version) . $file;
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestVersion() {
    $information = $this->getInformation();

    if (isset($information['lastversion'])) {
      return $information['lastversion'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInformation() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (isset($data[0])) {
      return $data[0];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function search($library) {
    $this->setLibrary($library);
    $this->available = NULL;

    if (!$this->isAvailable()) {
      return array();
    }

    $data = $this->request($this->getURL(__FUNCTION__));

    $results = array();
    foreach ($data as $library) {
      $results[] = $library['name'];
    }
    return $results;
  }

}
