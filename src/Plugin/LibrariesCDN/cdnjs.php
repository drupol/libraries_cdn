<?php
/**
 * @file
 * Plugin: CDNJS.
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
    if (isset($this->configuration['available'])) {
      return (bool) $this->configuration['available'];
    }

    $data = $this->query($this->getURL(__FUNCTION__));

    if (isset($data['total']) && $data['total'] !== 0) {
      $this->configuration['available'] = TRUE;
      return TRUE;
    }
    else {
      $this->configuration['available'] = FALSE;
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    $data = $this->query($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    return array_map(function($v) {
      return $v['version'];
    }, $data['results'][0]['assets']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles(array $versions = array()) {
    $data = $this->query($this->getURL(__FUNCTION__)) + array('assets' => array());

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
    $data = $this->query($this->getURL(__FUNCTION__));

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

    $data = $this->query($this->getURL(__FUNCTION__));

    return array_map(function($v) {
      return $v['name'];
    }, $data['results']);
  }

}
