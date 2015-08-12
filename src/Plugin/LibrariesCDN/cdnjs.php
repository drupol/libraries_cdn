<?php
/**
 * @file
 * Component: CDNJS.
 */

namespace Drupal\libraries_cdn\Plugin\LibrariesCDN;

use Drupal\Component\Plugin\PluginBase;
use Drupal\libraries_cdn\Component\Annotation\LibrariesCDNPlugin;
use Drupal\libraries_cdn\Types\CDNBase;
use Drupal\libraries_cdn\Types\CDNBaseInterface;

/**
 * Class CDNJS.
 *
 * @LibrariesCDNPlugin(
 *  id = "cdnjs",
 *  description = "CDNJS Integration"
 * )
 */
class CDNJS extends CDNBase implements CDNBaseInterface {
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
      'isAvailable' => 'http://api.cdnjs.com/libraries?search=%s',
      'getInformation' => 'http://api.cdnjs.com/libraries?search=%s&fields=version,description,homepage,keyword,maintainers',
      'getVersions' => 'http://api.cdnjs.com/libraries?search=%s&fields=assets',
      'getFiles' => 'http://api.cdnjs.com/libraries?search=%s&fields=assets',
      'convertFiles' => '//cdnjs.cloudflare.com/ajax/libs/%s/%s/',
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

    if ($data['total'] !== 0) {
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

    $results = array();
    foreach ((array) $data['results'][0]['assets'] as $asset) {
      $results[] = $asset['version'];
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles(array $versions = array()) {
    $data = $this->request($this->getURL(__FUNCTION__)) + array('assets' => array());

    if (!$this->isAvailable()) {
      return array();
    }

    $results = array();
    foreach ((array) $data['results'][0]['assets'] as $asset) {
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
  public function getInformation() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    return $data['results'][0];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestVersion() {
    $information = $this->getInformation();
    return $information['version'];
  }

  /**
   * {@inheritdoc}
   */
  public function search($library) {
    $this->setLibrary($library);
    $this->available = NULL;

    // Looks like it's impossible to do a search with the API on CDNJS.
    $result = array();
    if ($this->isAvailable()) {
      $result[] = $this->getLibrary();
    }
    return $result;
  }

}
