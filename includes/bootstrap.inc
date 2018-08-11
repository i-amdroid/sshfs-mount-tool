<?php

/**
 * @file
 * Provides initial config and basic functions.
 */

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;

$preferences['home_path'] = $_SERVER['HOME'];
$preferences['current_path'] = exec('pwd');
$preferences['mount_path'] = '~/mnt';

$preferences['user_config_file'] = $preferences['home_path'] . '/.config/smt/smt.yml';
$preferences['current_config_file'] = $preferences['current_path'] . '/smt.yml';
$preferences['project_info_file'] = __DIR__ . '/../composer.json';

$preferences['os_functions_inc'] = init();
require $preferences['os_functions_inc'];

$preferences['global'] = FALSE;

/**
 * Determine OS and return corresponding inc file.
 *
 * @return
 *  Filename of inc file.
 */
function init() {
  switch (PHP_OS) {
    case 'Darwin':
      return 'macos.inc';
      break;
    case 'Linux':
      return 'nix.inc';
      break;
    default:
      echo 'Unsupported operation system' . PHP_EOL;
      exit(1);
      break;
  }
  return;
}

/**
 * Show script version.
 *
 * @return
 *  Version number or Undefined.
 */
function get_version() {
  global $preferences;
  $project_info = file_get_contents($preferences['project_info_file']);
  $project_info = json_decode($project_info, TRUE);
  if (isset($project_info['version'])) {
    return $project_info['version'];
  }
  else {
    return 'Undefined';
  }
}

/**
 * Determine and return config file.
 *
 * @return
 *  Path to config file.
 */
function get_config_file() {
  global $preferences;
  // global option is set
  if ($preferences['global']) {
    return $preferences['user_config_file'];
  }
  // no global option, but exist config in current folder 
  elseif (file_exists($preferences['current_config_file'])) {
    return $preferences['current_config_file'];
  }
  return $preferences['user_config_file'];
}

/**
 * Parse config file and return config array
 *
 * @param $config_file
 *  Optional. Path to config file (in YAML format).
 *  If provided, will be used provided file.
 *  Else will be used default config file.
 *
 * @return
 *  Array of config values.
 *  If config file empty or not exists, empty array will be returned.
 */
function get_config($config_file = FALSE) {
  if (!$config_file) {
    $config_file = get_config_file();
  }
  if (file_exists($config_file)) {
    return Yaml::parseFile($config_file);
  }
  else {
    return [];
  }
}

/**
 * Save configuration array to config file.
 *
 * @param $config
 *  Array with configuration.
 *
 * @param $config_file
 *  Path to config file (in YAML format).
 *
 * @return
 *  TRUE if config saved successfully.
 */
function set_config($config, $config_file) {
  $yaml = Yaml::dump($config, 4, 2);

  // if config file not exist yet, check and create folder for it if needed
  if (!file_exists($config_file)) {
    $config_file_dir = dirname($config_file);
    if (!is_dir($config_file_dir)) {
      mkdir($config_file_dir, 0777, TRUE);
    }
  }

  // @todo refactor to use Symfony output and return proper values
  // https://api.symfony.com/4.1/Symfony/Component/Console/Output/ConsoleOutput.html
  if (file_put_contents($config_file, $yaml)) {
    chmod($config_file, 0777);
    return TRUE;
  }
  else {
    echo 'Error saving configuration.' . PHP_EOL;
    exit(1);
  }
}

/**
 * Return all connections from configuration.
 *
 * @return
 *  Array of connections or empty array.
 */
function get_connections() {
  $config = get_config();
  if (isset($config['connections'])) {
    return $config['connections'];
  }
  else {
    return [];
  }
}

/**
 * Return settings of a connection from configuration.
 *
 * @param $cid
 *  Connection ID.
 *  Should be provided valid ID.
 *
 * @return
 *  Array of connection settings.
 */
function get_connection_settings($cid) {
  $config = get_config();
  return $config['connections'][$cid];
}

/**
 * Add connection settings to config
 * and initiate saving it to config file.
 *
 * @param $cid
 *  Connection ID.
 *
 * @param $connection_settings
 *  Array of a connection settings.
 *
 * @param $input
 *  Input Interface.
 *
 * @param $output
 *  Output Interface.
 *
 * @param $helper
 *  Helper function.
 *
 * @param $use_current_dir
 *  Optional. Flag for use current directory for saving config file.
 *
 * @return
 *  Result of saving function (TRUE if config saved successfully).
 */
function set_connection_settings($cid, $connection_settings, $input, $output, $helper, $use_current_dir = FALSE) {
  global $preferences;
  // save to current dir, no config in current dir, should not load global
  if ($use_current_dir) {
    $config = get_config($preferences['current_config_file']);
  }
  else {
    $config = get_config();
  }
  $connection_exist = FALSE;
  if (isset($config['connections'])) {
    foreach ($config['connections'] as $key => $value) {
      if ($key == $cid) {
        // ask for rewrite
        $overwrite_question = new Question('Connection "' . $cid . '" already exists, overwrite it? [<comment>no</comment>]? ');
        $overwrite_question->setValidator(function ($answer) {
          if ($answer == 'y' || $answer == 'Y' || $answer == 'yes' || $answer == 'Yes' || $answer == 'YES') {
            return TRUE;
          }
          elseif ($answer == '' || $answer == 'c' || $answer == 'C' || $answer == 'cancel' || $answer == 'Cancel' || $answer == 'CANCEL' ||
            $answer == 'n' || $answer == 'N' || $answer == 'no' || $answer == 'No' || $answer == 'NO') {
            // return from callback without value
            return;
          }
          else {
            throw new \RuntimeException(
              $answer . ' is not a valid answer.'
            );
          }
        });

        $overwrite = $helper->ask($input, $output, $overwrite_question);

        if ($overwrite) {
          $config['connections'][$key] = $connection_settings;
          $connection_exist = TRUE;
        }
        else {
          // canceled
          // @todo refactor to return proper values to command function
          // return;
          exit(0);
        }

      }
    }
  }
  if (!$connection_exist) {
    $config['connections'][$cid] = $connection_settings;
  }
  if ($use_current_dir) {
    $config_file = $preferences['current_config_file'];
  }
  else {
    $config_file = $preferences['user_config_file'];
  }
  return set_config($config, $config_file);
}

/**
 * Remove connection settings from config
 * and initiate saving updated config to config file.
 *
 * @param $cid
 *  Connection ID.
 *
 * @return
 *  Result of saving function (TRUE if config saved successfully).
 */
function remove_connection_settings($cid) {
  $config_file = get_config_file();
  $config = get_config();
  foreach ($config['connections'] as $key => $value) {
    if ($key == $cid) {
      unset($config['connections'][$key]);
    }
  }
  return set_config($config, $config_file);
}

/**
 * Return subset of connection settings from configuration:
 *  'n' - number;
 *  'cid' - connection ID;
 *  'title' - connection title;
 *  'status' - connection status: Mounted/Not mounted.
 *
 * @param $mounted_only
 *  Flag. Optional. Return only mounted connections.
 *
 * @return
 *  Array of connection data.
 */
function get_connections_data($mounted_only = FALSE) {
  $connections = get_connections();
  $mounts = get_mounts();
  $connections_data = [];
  $i = 1;

  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $connections_data[] = [
        'n' => $i,
        'cid' => $cid,
        'title' => $connection_settings['title'],
        'status' => 'Mounted',
      ];
      $i++;
    }
    elseif (!$mounted_only) {
      $connections_data[] = [
        'n' => $i,
        'cid' => $cid,
        'title' => $connection_settings['title'],
        'status' => 'Not mounted',
      ];
      $i++;
    }
  }

  return $connections_data;
}

/**
 * Prepare formatted table from connections list.
 *
 * @param $connections_data
 *  Array of connections data.
 *
 * @param $output
 *  Output Interface.
 *
 * @return
 *  Table object.
 */
function gen_connections_table($connections_data, $output) {
  $table = new Table($output);
  $table->setHeaders([
    '#',
    'ID',
    'Title',
    'Status',
  ]);

  foreach ($connections_data as $row => $connection_data) {
    if ($connection_data['status'] == 'Mounted') {
      $table->addRow([
        '<info>' . $connection_data['n'] . '</info>',
        '<info>' . $connection_data['cid'] . '</info>',
        '<info>' . $connection_data['title'] . '</info>',
        '<info>' . $connection_data['status'] . '</info>',
      ]);
    }
    else {
      $table->addRow([
        $connection_data['n'],
        $connection_data['cid'],
        $connection_data['title'],
        $connection_data['status'],
      ]);
    }
  }

  $table->setStyle('compact');
  $tableStyle = $table->getStyle();
  $tableStyle->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
  $tableStyle->setCellRowContentFormat('%s  ');
  $tableStyle->setVerticalBorderChar('');

  return $table;
}

/**
 * Prepare formatted table from connection settings.
 *
 * @param $cid
 *  Connection ID.
 *
 * @param $connection_settings
 *  Array of connection settings.
 *
 * @param $output
 *  Output Interface.
 *
 * @return
 *  Table object.
 */
function gen_connection_settings_table($cid, $connection_settings, $output) {
  $table = new Table($output);
  $table->setHeaders([
    'Setting',
    'Value',
  ]);

  $table->addRow([
    'id',
    $cid,
  ]);

  foreach ($connection_settings as $key => $value) {
    if ($key == 'options') {
      $table->addRow([
        $key,
        implode(',', $value),
      ]);
    }
    elseif ($key == 'password' && $value) {
      $table->addRow([
        $key,
        '[password]',
      ]);
    }
    else {
      $table->addRow([
        $key,
        $value,
      ]);
    }
  }

  $table->setStyle('compact');
  $tableStyle = $table->getStyle();
  $tableStyle->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
  $tableStyle->setCellRowContentFormat('%s    ');
  $tableStyle->setVerticalBorderChar('');

  return $table;
}

/**
 * Return connection ID by mount point.
 *
 * @param $mount_point
 *  Path to mount point in relative or absolute format.
 *
 * @return
 *  Connection ID or FALSE if connection was not resolved.
 */
function get_cid($mount_point) {
  global $preferences;
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($mount_point == $connection_settings['mount']) {
      return $cid;
    }
    elseif (substr($connection_settings['mount'], 0, 1) == '~') {
      $absolute_path = $preferences['home_path'] . substr($connection_settings['mount'], 1);
      if ($mount_point == $absolute_path) {
        return $cid;
      }
    }
  }
  return FALSE;
}

/**
 * Check that user input match connection ID in config
 * or provided connections list.
 *
 * @param $input
 *  Some string.
 *
 * @param $connections_data
 *  Optional. Array of connections.
 *
 * @return
 *  Connection ID or FALSE if connection was not resolved.
 */
function match_cid($input, $connections_data = FALSE) {
  if (!$connections_data) {
    $connections = get_connections();
    foreach ($connections as $cid => $connection_settings) {
      if ($input == $cid) {
        return $input;
      }
    }
  }
  else {
    foreach ($connections_data as $connection => $connection_data) {
      if ($input == $connection_data['cid']) {
        return $input;
      }
    }
  }
  return FALSE;
}

/**
 * Check that user input match connection ID or number
 * in provided connection IDs list.
 *
 * @param $answer
 *  Some string.
 *
 * @param $connections_data
 *  Array of connections data.
 *
 * @return
 *  Connection ID or NULL if connection ID was not resolved.
 */
function validate_answer_as_connection($answer, $connections_data) {
  $cid = NULL;

  // Answer looks like a number
  if (filter_var($answer, FILTER_VALIDATE_INT)) {
    foreach ($connections_data as $connection => $connection_data) {
      if ($answer == $connection_data['n']) {
        $cid = $connection_data['cid'];
      }
    }
  }
  // Answer looks like a cid
  else {
    foreach ($connections_data as $connection => $connection_data) {
      if ($answer == $connection_data['cid']) {
        $cid = $connection_data['cid'];
      }
    }
  }

  return $cid;
}

/**
 * Determine providing of connection ID and return it.
 * If connection ID not provided:
 *  * if only one connection exist in config, it will be returned
 * automatically;
 *  * if multiple connections exists in config, user will prompted to choose
 * one.
 *
 * @param $input
 *  Input Interface.
 *
 * @param $output
 *  Output Interface.
 *
 * @param $helper
 *  Helper function.
 *
 * @return
 *  Connection ID or 0 if connection was not resolved.
 */
function cid_resolver($input, $output, $helper) {

  if ($input->getArgument('connection_id')) {
    $cid = $input->getArgument('connection_id');
    if (!match_cid($cid)) {
      $output->writeln($cid . ' is not a valid connection ID');
      return 0;
    }
  }
  // cid not provided
  else {
    $connections_data = get_connections_data();

    // no saved connections
    if (empty($connections_data)) {
      $output->writeln('No saved connections');
      // not an error
      return 0;
    }
    // one connection
    elseif (count($connections_data) == 1) {
      $cid = $connections_data[0]['cid'];
    }
    // multiple connections
    else {
      $table = gen_connections_table($connections_data, $output);
      $table->render();

      $question = new Question('Number or ID of connection [<comment>cancel</comment>]: ');
      $question->setValidator(function ($answer) use ($connections_data) {
        if ($answer == '' || $answer == 'c' || $answer == 'C' || $answer == 'cancel' || $answer == 'Cancel' || $answer == 'CANCEL') {
          // return from callback without $cid
          return;
        }
        $cid = validate_answer_as_connection($answer, $connections_data);
        if (!$cid) {
          throw new \RuntimeException(
            $answer . ' is not a valid connection number or ID'
          );
        }
        else {
          return $cid;
        }
      });

      $cid = $helper->ask($input, $output, $question);

      if (!$cid) {
        // canceled
        return 0;
      }
    }
  }
  return $cid;
}

/**
 * Return shell command for run in new tab of terminal.
 *
 * @param $cmd
 *  Command for run.
 *
 * @return
 *  Result of command execution.
 */
function gen_terminal_cmd($cmd) {
  global $preferences;
  global $terminals;
  $terminal = $preferences['terminal'];
  return $terminals[$terminal]['cmd_prefix'] . $cmd . $terminals[$terminal]['cmd_suffix'];
}
