<?php

/**
 * Callback for the cdn page
 */
function libraries_cdn_ui_admin_page_callback() {
  $form = drupal_get_form('libraries_cdn_ui_browser');
  return drupal_render($form);
}

function libraries_cdn_ui_browser($form, &$form_state) {
  if (!isset($form_state['stage'])) $form_state['stage'] = 'step1';
  $form = array();

  $form['#tree'] = TRUE;

  $form_state['stage'] = isset($form_state['stage']) ? $form_state['stage'] : 1;



  $form_stages = array(
    'step1' => 1,
    'step2' => 2,
    'step3' => 3,
    'step4' => 4,
  );

  if (isset($form_stages[$form_state['stage']])) {
    $current_step = $form_stages[$form_state['stage']];
  }
  else {
    $current_step = 1;
  }

  $stages = array(
    1 => array('data' => 'Choose the library'),
    2 => array('data' => 'Select the version and files'),
    3 => array('data' => 'Review your choice'),
    4 => array('data' => 'Submit'),
  );

  $stages[$current_step]['class'] = array('active');

  $form['header'] = array(
    '#type' => 'item',
    '#description' => $stages[$current_step]['data'],
    '#title' => 'Step ' . $current_step,
  );

  switch ($form_state['stage']) {
    case 'step1':
      return libraries_cdn_ui_browser_step1($form, $form_state);
      break;

    case 'step2':
      return libraries_cdn_ui_browser_step2($form, $form_state);
      break;

    case 'step3':
      return libraries_cdn_ui_browser_step3($form, $form_state);
      break;

    case 'step4':
      return libraries_cdn_ui_browser_step4($form, $form_state);
      break;
  }

  return $form;
}

function libraries_cdn_ui_browser_step1($form, &$form_state) {
  $form['library'] = array(
    '#type' => 'textfield',
    '#title' => 'Search a library',
    '#autocomplete_path' => 'libraries_cdn/autocomplete',
    '#default_value' => isset($form_state['values']['library']) ? $form_state['values']['library'] : '',
  );

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Next')
  );

  return $form;
}

function libraries_cdn_ui_library_autocomplete_callback($search) {
  $matches = array();
  $search_result = \Drupal\libraries_cdn\LibrariesCDN::search($search);

  foreach($search_result as $cdn => $data) {
    foreach($data as $name => $library) {
      $matches[$cdn . ':' . $library['name']] = str_ireplace($search, '<strong>'. $search . '</strong>', $library['name']) . ' (' . $cdn .')';
    }
  }

  drupal_json_output($matches);
}

function libraries_cdn_ui_browser_step2($form, &$form_state) {
  list($cdn, $library) = explode(':', $form_state['values']['library'], 2);

  $form['library'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['library'],
  );

  $id = $cdn . ':' . $library;
  \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn, $library);
  $plugin = \Drupal\libraries_cdn\LibrariesCDN::getPlugin();
  $files = $plugin->getFiles();

  $form['version'] = array(
    '#type' => 'select',
    '#title' => 'Versions available',
    '#attributes' => array(
      'id' => $id.'-version',
    ),
    '#options' => array_combine(array_keys($files), array_keys($files)),
    '#default_value' => isset($form_state['values']['version']) ? $form_state['values']['version'] : '',
  );

  $form_state['values'] += array('files' => array());

  foreach($files as $version => $assets) {
    $version_files = $files[$version];

    $form_state['values']['files'] += array($version => array('files' => array()));

    $form['files'][$version] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          ":input[id='$id-version']" => array('value' => $version),
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
      $available_locally = $plugin->isLocalAvailable($file, $version) ? 'yes' : 'no';
      $local = $plugin->getLocalFileName($file, $version);

      $options[$id.':'.$version.':'.$index] = array(
        'url' => $file,
        'local' => $local,
        'isLocal' => $available_locally
      );
    }

    $form['files'][$version]['files'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => TRUE,
      '#empty' => t('No file found'),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#default_value' => drupal_map_assoc($form_state['values']['files'][$version]['files']),
    );
  }

  $form['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back')
  );

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Next')
  );

  return $form;
}

function libraries_cdn_ui_browser_step2_version_callback($form, $form_state) {
  return $form['files'];
}

function libraries_cdn_ui_browser_step3($form, &$form_state) {
  list($cdn, $library) = explode(':', $form_state['values']['library'], 2);
  \Drupal\libraries_cdn\LibrariesCDN::setPlugin($cdn, $library);
  $files = \Drupal\libraries_cdn\LibrariesCDN::getFiles($form_state['values']['version']);
  $indexes_selected = array_filter($form_state['values']['files'][$form_state['values']['version']]['files']);
  $files_selected = array();
  foreach($indexes_selected as $id) {
    list($cdn, $library, $version, $index) = explode(':', $id, 4);
    $files_selected[] = array('data' => $files[$version][$index]);
  }

  $form['validation']['library'] = array(
    '#type' => 'item',
    '#title' => 'Library',
    '#markup' => $library
  );

  $form['validation']['version'] = array(
    '#type' => 'item',
    '#title' => 'Version',
    '#markup' => $form_state['values']['version']
  );

  $form['validation']['files'] = array(
    '#type' => 'item',
    '#title' => 'Files to download',
    '#markup' => theme_item_list(array('items' => $files_selected, 'type' => 'ul', 'attributes' => array(), 'title' => ''))
  );

  $form['library'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['library'],
  );

  $form['version'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['version'],
  );

  $form['files'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['files'],
  );

  $form['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back')
  );

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Next')
  );

  return $form;
}

function libraries_cdn_ui_browser_step4($form, &$form_state) {
  $form['library'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['library'],
  );

  $form['version'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['version'],
  );

  $form['files'] = array(
    '#type' => 'value',
    '#default_value' => $form_state['values']['files'],
  );

  $form['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back')
  );

  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

function libraries_cdn_ui_browser_submit($form, &$form_state) {
    switch ($form_state['stage']) {
    case 'step4':
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
      $form_state['complete'] = TRUE;
      break;
    default:
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
      $form_state['new_stage'] = libraries_cdn_ui_browser_next_stage($form, $form_state);
      break;
  }

  if (isset($form_state['complete'])) {
    $files = array_filter($form_state['values']['files'][$form_state['values']['version']]['files']);

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

    drupal_flush_all_caches();
    return;
  }

  if ($form_state['triggering_element']['#value'] == 'Back') {
    $form_state['new_stage'] = libraries_cdn_ui_browser_previous_stage($form, $form_state);
  }

  if (isset($form_state['multistep_values']['form_build_id'])) {
    $form_state['values']['form_build_id'] = $form_state['multistep_values']['form_build_id'];
  }
  $form_state['multistep_values']['form_build_id'] = $form_state['values']['form_build_id'];
  $form_state['stage'] = $form_state['new_stage'];
  $form_state['rebuild'] = TRUE;

}

function libraries_cdn_ui_browser_next_stage($form, &$form_state) {
  switch ($form_state['stage']) {
    case 'step1':
      return 'step2';
      break;
    case 'step2':
      return 'step3';
      break;
    case 'step3':
      return 'step4';
      break;
    case 'step4':
      return 'complete';
      break;
  }
}

function libraries_cdn_ui_browser_previous_stage($form, &$form_state) {
  switch ($form_state['stage']) {
    case 'step2':
      return 'step1';
      break;
    case 'step3':
      return 'step2';
      break;
    case 'step4':
      return 'step3';
      break;
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
  $files = array_filter($files);
  $data = array();
  $libraries = variable_get('libraries_cdn_libraries', array());

  foreach($files as $id) {
    list($cdn,$library,$version,$index) = explode(':', $id);
    $data[$cdn][$library][$version][] = array('index' => $index);
  }

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

  return $libraries;
}