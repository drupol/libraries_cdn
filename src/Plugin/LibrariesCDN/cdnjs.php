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
class CDNJS extends CDNBase {
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
      'getInformation' => 'http://api.cdnjs.com/libraries/%s',
      'getVersions' => 'http://api.cdnjs.com/libraries?search=%s&fields=assets',
      'getFiles' => 'http://api.cdnjs.com/libraries/%s',
      'search' => 'http://api.cdnjs.com/libraries?search=%s',
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
    }
    else {
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
    foreach ((array) $data['assets'] as $asset) {
      $results[$asset['version']] = $this->convertFiles($asset['files'], $asset['version']);
    }

    return empty($versions) ? $results : array_intersect_key($results, array_combine(array_values($versions), array_values($versions)));
  }

  /**
   * {@inheritdoc}
   */
  public function getInformation() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    return $data;
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

    if (!$this->isAvailable()) {
      return array();
    }

    $data = $this->request($this->getURL(__FUNCTION__));

    $results = array();
    foreach ($data['results'] as $library) {
      $results[] = $library['name'];
    }
    return $results;
  }

}
