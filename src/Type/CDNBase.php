<?php
/**
 * @file
 * Class Component.
 */

namespace Drupal\libraries_cdn\Types;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class Component.
 */
abstract class CDNBase extends PluginBase implements CDNBaseInterface {
  public function setConfiguration(array $configuration = array()) {
    $this->configuration = $configuration;
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function setLibrary($library) {
    $this->configuration['library'] = $library;
  }

  public function getLibrary() {
    return $this->configuration['library'];
  }

  public function setURL($identifier, $url) {
    $this->configuration['urls'][$identifier] = $url;
  }

  public function getURL($identifier) {
    return $this->configuration['urls'][$identifier];
  }

  public function setURLs(array $urls = array()) {
    $this->configuration['urls'] = $urls;
  }

  public function getURLs() {
    return $this->configuration['urls'];
  }

  public function request($url, array $options = array()) {
    $request = drupal_http_request(sprintf($url, $this->getLibrary()));
    if ($request->code != 200) {
      return FALSE;
    }
    return json_decode($request->data, TRUE);
  }

  public function getLatestVersion() {
    return FALSE;
  }
}
