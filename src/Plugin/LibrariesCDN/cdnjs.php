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
      'getVersions' => 'http://api.cdnjs.com/libraries/%s',
      'getFiles' => 'http://api.cdnjs.com/libraries/%s',
      'search' => 'http://api.cdnjs.com/libraries?search=%s',
      'convertFiles' => '//cdnjs.cloudflare.com/ajax/libs/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function formatData($function, array $data = array()) {
    switch ($function) {
      case 'search':
      case 'isAvailable':
        return (array) $data['results'];

      case 'getVersions':
      case 'getFiles':
        return (array) $data['assets'];

      case 'getLatestVersion':
        return $data['version'];

      default:
        return $data;
    }
  }

}
