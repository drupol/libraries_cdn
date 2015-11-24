<?php

/**
 * Callback for the cdn page
 */
function libraries_cdn_ui_admin_page_callback() {
  $form = drupal_get_form('libraries_cdn_ui_browser');
  return drupal_render($form);
}

/**
 * Form.
 */
function libraries_cdn_ui_browser($form, $form_state) {
  $form['libraries_cdn'] = array(
    '#type' => 'fieldset',
    '#title' => 'CDN related options',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $cdns = implode(', ', \Drupal\libraries_cdn\LibrariesCDN::getAvailableCDN());
  $form['libraries_cdn']['search'] = array(
    '#type' => 'textfield',
    '#title' => 'Search a library',
    '#description' => 'Search for a library in each the available CDN: ' . $cdns,
    '#default_value' => isset($form_state['input']['search']) ? $form_state['input']['search'] : ''
  );

  $form['libraries_cdn']['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Search',
  );

  $value = variable_get('libraries_cdn_libraries', array());

  $form['libraries_cdn_results'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
    '#validated' => TRUE,
  );

  if (isset($form_state['storage']['results'])) {
    $results = $form_state['storage']['results'];

    foreach($results as $cdn => $data) {
      $form['libraries_cdn_results']['results'][$cdn] = array(
        '#type' => 'fieldset',
        '#title' => 'Results on ' . $cdn,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      foreach($data as $library) {
        $id = $cdn . ':' . $library['name'];
        \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn, $library['name']);
        $plugin = \Drupal\libraries_cdn\LibrariesCDN::getPlugin();
        $files = $plugin->getFiles();

        $form['libraries_cdn_results']['results'][$cdn][$id] = array(
          '#type' => 'fieldset',
          '#collapsible' => FALSE,
          '#title' => $library['name'],
          '#description' => $library['description'],
        );

        $versions = array();
        foreach($library['assets'] as $asset) {
          $version_files = $files[$asset['version']];
          $versions[] = $asset['version'];

          $form['libraries_cdn_results']['results'][$cdn][$id]['files']['version-' . $asset['version']] = array(
            '#type' => 'fieldset',
            '#weight' => 5,
            '#title' => 'Files for version ' . $asset['version'],
            '#states' => array(
              'visible' => array(
                ":input[id='$id-versions']" => array('value' => $asset['version']),
              ),
            ),
          );

          $header = array(
            'url' => 'File',
            'local' => 'Local',
            'isLocal' => 'Available'
          );

          $options = array();
          foreach($version_files as $index => $file) {
            $available_locally = $plugin->isLocalAvailable($file, $asset['version']) ? 'yes' : 'no';
            $local = $plugin->getLocalFileName($file, $asset['version']);

            $options[$id.':'.$asset['version'].':'.$index] = array(
              'url' => $file,
              'local' => $local,
              'isLocal' => $available_locally
            );
          }

          $form['libraries_cdn_results']['results'][$cdn][$id]['files']['version-' . $asset['version']]['files'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $options,
            '#multiple' => TRUE,
            '#empty' => t('No file found'),
          );
        }

        $form['libraries_cdn_results']['results'][$cdn][$id]['versions'] = array(
          '#type' => 'select',
          '#weight' => 4,
          '#title' => 'Versions available',
          '#attributes' => array(
            'id' => $id.'-versions',
          ),
          '#options' => array_combine(array_values($versions), array_values($versions))
        );

      }
    }

    $form['libraries_cdn_results']['buttons']['download'] = array(
      '#type' => 'submit',
      '#value' => 'Download selected files'
    );
    $form['libraries_cdn_results']['buttons']['delete'] = array(
      '#type' => 'submit',
      '#value' => 'Delete selected files'
    );
  }

  return $form;
}

/**
 * Submit handler of the form.
 *
 * @param $form
 * @param $form_state
 */
function libraries_cdn_ui_browser_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
  $search = $form_state['values']['search'];
  $data = \Drupal\libraries_cdn\LibrariesCDN::search($search);
  $form_state['storage']['results'] = $data;

  $form_state['values'] += array('libraries_cdn_results' => array('results' => array()));

  $download = $form_state['values']['libraries_cdn_results']['results'];
  $file_to_download = array();
  foreach($download as $cdn => $library) {
    foreach($library as $asset) {
      foreach($asset['files'] as $version => $files) {
        $file_to_download += $files['files'];
      }
    }
  }
  $file_to_download = array_filter($file_to_download);

  foreach($file_to_download as $id) {
    list($cdn,$library,$version,$index) = explode(':', $id);
    \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn, $library);
    $plugin = \Drupal\libraries_cdn\LibrariesCDN::getPlugin();
    $plugin->getLocalCopy(array($version), array($index));
  }

}
