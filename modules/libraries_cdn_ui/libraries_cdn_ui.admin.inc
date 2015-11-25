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
    1 => array('data' => '1. Choose the library'),
    2 => array('data' => '2. Select the version and files'),
    3 => array('data' => '3. Download the files'),
    4 => array('data' => '4. Finishing'),
  );

  $stages[$current_step]['class'] = array('active');

  $form['header'] = array(
    '#theme' => 'item_list',
    '#title' => 'Steps (' . $current_step .')',
    '#items' => $stages,
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
    '#title' => 'Library',
    '#autocomplete_path' => 'libraries_cdn/autocomplete',
    '#default_value' => $form_state['values']['library'],
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
    '#weight' => 4,
    '#title' => 'Versions available',
    '#attributes' => array(
      'id' => $id.'-version',
    ),
    '#options' => array_combine(array_keys($files), array_keys($files)),
    '#default_value' => $form_state['values']['version']
  );

  $form['files'] = array(
    '#weight' => 5,
  );

  foreach($files as $version => $assets) {
    $version_files = $files[$version];

    $form['files'][$version] = array(
      '#type' => 'container',
      '#title' => 'Files for version ' . $version,
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

    dpm($form_state);

    $form['files'][$version]['files'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => TRUE,
      '#empty' => t('No file found'),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#default_value' => drupal_map_assoc($form_state['values']['files']),
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

  $files = array_filter($form_state['values']['files']);

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

  $library = _libraries_cdn_ui_build_variable($files);

  $form['textarea'] = array(
    '#type' => 'textarea',
    '#default_value' => print_r($library, 1)
  );

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
    //batch_set($batch);
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

function libraries_cdn_ui_browser_step4($form, &$form_state) {
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
    default:
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
      $form_state['new_stage'] = libraries_cdn_ui_browser_next_stage($form, $form_state);
      break;

  }
  if (isset($form_state['complete'])) drupal_goto('complete-page');

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

/**l
 * Submit handler of the form.
 *
 * @param $form
 * @param $form_state
 */
function libraries_cdn_ui_browser_old_submit($form, &$form_state) {
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
  return $libraries;
  //variable_set('libraries_cdn_libraries', $libraries);
}