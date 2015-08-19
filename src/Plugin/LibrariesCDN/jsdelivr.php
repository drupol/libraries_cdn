<?php
/**
 * @file
 * Plugin: jsDelivr.
 */

namespace Drupal\libraries_cdn\Plugin\LibrariesCDN;

use Drupal\Component\Plugin\PluginBase;
use Drupal\libraries_cdn\Component\Annotation\LibrariesCDNPlugin;
use Drupal\libraries_cdn\Types\CDNBase;

/**
 * Class JSDelivr.
 *
 * @LibrariesCDNPlugin(
 *  id = "jsdelivr",
 *  description = "jsDelivr Integration"
 * )
 */
class JSDelivr extends CDNBase {
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
  public function formatData($function, array $data = array()) {
    switch ($function) {
      case 'getVersions':
        return (array) $data[0]['versions'];

      case 'getFiles':
        return (array) $data[0]['assets'];

      case 'getLatestVersion':
        return $data['lastversion'];

      case 'getInformation':
        return array_shift($data);

      default:
        return $data;
    }
  }

}
