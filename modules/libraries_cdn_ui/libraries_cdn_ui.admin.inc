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
        '#title' => 'Results on ' . $cdn . ' (' . count($data) . ')',
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
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#title' => $library['name'],
          '#description' => $library['description'],
        );

        $form['libraries_cdn_results']['results'][$cdn][$id]['files'] = array(
          '#weight' => 5,
        );

        $versions = array();
        foreach($library['assets'] as $asset) {
          $version_files = $files[$asset['version']];
          $versions[] = $asset['version'];

          $form['libraries_cdn_results']['results'][$cdn][$id]['files']['version-' . $asset['version']] = array(
            '#type' => 'container',
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

/**l
 * Submit handler of the form.
 *
 * @param $form
 * @param $form_state
 */
function libraries_cdn_ui_browser_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
  $form_state['values'] += array('libraries_cdn_results' => array('results' => array()));

  if (isset($form_state['values']['search'])) {
    $search = $form_state['values']['search'];
  } else {
    return;
  }

  // Get all the search results from the available plugins.
  $data = \Drupal\libraries_cdn\LibrariesCDN::search($search);
  // Store the results in the form array so we can reuse this in the upper form.
  $form_state['storage']['results'] = $data;

  // Generate a simple array with the files to download.
  $data = $form_state['values']['libraries_cdn_results']['results'];
  $files = array();
  foreach($data as $library) {
    foreach($library as $version) {
      foreach($version['files'] as $asset) {
        $files += $asset['files'];
      }
    }
  }
  $files = array_filter($files);

  // Build the operations array for the batch.
  $operations = array();
  foreach($files as $id) {
    $operations[] = array('_libraries_cdn_ui_download_files', array($id));
  }
  $operations[] = array('_libraries_cdn_ui_build_variable', array($files));

  $batch = array(
    'title' => t('Libraries CDN API: Downloading files'),
    'operations' => $operations,
    'init_message' => t('Initializing...'),
    'progress_message' => t('Operation @current out of @total.'),
    'error_message' => t('Found some error.'),
    'file' => drupal_get_path('module', 'libraries_cdn_ui') . '/libraries_cdn_ui.admin.inc'
  );

  // Start the batch if there are files to download.
  if (!empty($files)) {
    batch_set($batch);
  }
}

/**
 * Function callback for the batch.
 *
 * @param $id
 *   The id pattern is: [CDN_ID]:[LIBRARY]:[VERSION]:[FILE_INDEX]
 */
function _libraries_cdn_ui_download_files($id) {
  list($cdn,$library,$version,$index) = explode(':', $id);
  \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn, $library);
  $plugin = \Drupal\libraries_cdn\LibrariesCDN::getPlugin();
  $plugin->getLocalCopy(array($version), array($index));
}

function _libraries_cdn_ui_build_variable($files) {
  $data = array();

  foreach($files as $id) {
    list($cdn,$library,$version,$index) = explode(':', $id);
    $data[$cdn][$library][$version][] = array('index' => $index);
  }

  $libraries = variable_get('libraries_cdn_libraries', array());
  foreach($data as $cdn => $libraries) {
    \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn);
    $plugin = \Drupal\libraries_cdn\LibrariesCDN::getPlugin();
    foreach($libraries as $library_name => $library) {
      $plugin->setLibrary($library_name);
      $information = $plugin->getInformation();
      foreach($library as $version => $asset) {
        $files = array();
        $library_files = $plugin->getFiles(array($version));
        $library_files = array_shift($library_files);
        foreach($asset as $file) {
          $ext = pathinfo($library_files[$file['index']], PATHINFO_EXTENSION);
          $files[$ext][] = drupal_realpath($plugin->getLocalFileName($library_files[$file['index']], $version));
        }
        $libraries[$information['name']] = array(
          'name' => $information['name'],
          'library path' => drupal_realpath($plugin->getLocalDirectoryName($version)),
          'version' => $version,
          'files' => $files
        );
      }
    }
  }
  variable_set('libraries_cdn_libraries', $libraries);
}