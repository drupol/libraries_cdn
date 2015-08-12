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
    $request = drupal_http_request(sprintf($url, $this->getLibrary()));
    if ($request->code != 200) {
      return FALSE;
    }
    return json_decode($request->data, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestVersion() {
    return FALSE;
  }
}
