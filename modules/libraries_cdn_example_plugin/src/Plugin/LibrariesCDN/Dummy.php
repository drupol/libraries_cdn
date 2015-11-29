<?php
/**
 * @file
 * Plugin: jsDelivr.
 */

namespace Drupal\libraries_cdn_example_plugin\Plugin\LibrariesCDN;

use Drupal\Component\Plugin\PluginBase;
use Drupal\libraries_cdn\Component\Annotation\LibrariesCDNPlugin;
use Drupal\libraries_cdn\Type\CDNBase;
use Drupal\service_container\Legacy\Drupal7;

/**
 * Class Dummy.
 *
 * @LibrariesCDNPlugin(
 *  id = "dummy",
 *  description = "Dummy CDN",
 *  arguments = {
 *    "@drupal7"
 *  }
 * )
 */
class Dummy extends CDNBase {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Drupal7 $drupal7) {
    if (empty($configuration['urls'])) {
      $configuration['urls'] = array();
    }

    $converFileUrl = url('libraries_cdn/dummy/files', array('absolute' => TRUE));
    $converFileUrl = parse_url($converFileUrl);
    array_shift($converFileUrl);
    $converFileUrl = implode($converFileUrl);

    $configuration['urls'] += array(
      'convertFiles' => '//' . $converFileUrl . '/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition, $drupal7);
  }

  /**
   * {@inheritdoc}
   */
  public function formatData($function, array $data = array()) {
    switch ($function) {
      case 'getVersions':
        return isset($data[0]) && isset($data[0]['versions']) ? $data[0]['versions'] : array();

      case 'getFiles':
        return isset($data[0]) && isset($data[0]['assets']) ? $data[0]['assets'] : array();

      case 'getLatestVersion':
        return isset($data['lastversion']) ? $data['lastversion'] : NULL;

      case 'getInformation':
        return isset($data[0]) ? $data[0] : array();

      default:
        return $data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search($library) {
    $this->setLibrary($library);
    return array(
      0 => array(
        'name' => drupal_html_class(drupal_clean_css_identifier($library))
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles(array $versions = array()) {
    $data = array();

    $keys = array_merge(range(0, 9), range('a', 'z'));
    $count_version = mt_rand(1, 5) + count($versions);
    $file_length = mt_rand(5, 10);

    for($i=0; $i<$count_version; $i++) {
      $files = array();
      $major = mt_rand(1,10);
      $minor = mt_rand(1,1000);

      for($j=0; $j<10; $j++) {
        $key = '';
        for ($k = 0; $k < $file_length; $k++) {
          $key .= $keys[array_rand($keys)];
        }
        $ext = ($j % 2) == 0 ? 'js' : 'css';
        $files[] = $key . '.' . $ext;
      }

      if (isset($versions[$i])) {
        $version = $versions[$i];
      } else {
        $version = $major . '.' . $minor;
      }

      $data[] = array(
        'files' => $files,
        'version' => $version,
      );
    }

    $results = array();
    foreach ($data as $asset) {
      if (isset($asset['version']) && isset($asset['files']) && is_array($asset['files'])) {
        $results[$asset['version']] = $this->convertFiles($asset['files'], $asset['version']);
      }
    }

    return empty($versions) ? $results : array_intersect_key($results, array_combine(array_values($versions), array_values($versions)));
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    $data = array();
    $count_version = mt_rand(1, 5);

    for($i=0; $i<$count_version; $i++) {
      $major = mt_rand(1,10);
      $minor = mt_rand(1,1000);

      $data['versions'][] = $major . '.' . $minor;
    }

    return array_filter($data);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInformation() {
    return array(
      array(
        'name' => 'dummy',
        'mainfile' => 'jquery.min.js',
        'lastversion' => '3.0.0-alpha1',
        'description' => 'jQuery is a fast and concise JavaScript Library that simplifies HTML document traversing, event handling, animating, and Ajax interactions for rapid web development. jQuery is designed to change the way that you write JavaScript.',
        'homepage' => 'http://jquery.com/',
        'github' => 'https://github.com/jquery/jquery',
        'author' => 'jQuery Foundation',
      )
    );
  }

}
