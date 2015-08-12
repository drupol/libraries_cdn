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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['urls'])) {
      $configuration['urls'] = array();
    }
    $configuration['urls'] += array(
      'isAvailable' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries/%s',
      'getInformation' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=name,mainfile,lastversion,description,homepage,github,author',
      'getVersions' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=versions',
      'getFiles' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=assets',
      'convertFiles' => 'http://cdn.jsdelivr.net/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public function isAvailable() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (isset($data[0])) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getVersions() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (!$this->isAvailable()) {
      return array();
    }

    return $data[0]['versions'];
  }

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

  public function convertFiles(array $files = array(), $version) {
    $results = array();
    foreach ($files as $file) {
      $results[] = sprintf($this->getURL(__FUNCTION__), $this->getLibrary(), $version) . $file;
    }
    return $results;
  }

  public function getLatestVersion() {
    $information = $this->getInformation();

    if (isset($information['lastversion'])) {
      return $information['lastversion'];
    }
    return FALSE;
  }

  public function getInformation() {
    $data = $this->request($this->getURL(__FUNCTION__));

    if (isset($data[0])) {
      return $data[0];
    }
    return FALSE;
  }

}
