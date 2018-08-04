<?php

/**
 * @file
 * Provides initial config and basic functions.
 */

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Helper\Table;
use LucidFrame\Console\ConsoleTable; // ! tbr

$preferences['home_path'] = $_SERVER['HOME'];
$preferences['current_path'] = exec('pwd');

$preferences['user_config_file'] = $preferences['home_path'] . '/.config/smt/smt.yml';
$preferences['current_config_file'] = $preferences['current_path'] . '/smt.yml';
$preferences['project_info_file'] = __DIR__ . '/../composer.json';

$preferences['os_functions_inc'] = init();
// require_once __DIR__ . '/../includes/' . $preferences['os_functions_inc'];
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
    return array();
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
 *  TRUE if config saved sucessfully.
 */
function set_config($config, $config_file) {
  $yaml = Yaml::dump($config, 4, 2);

  // if config file not exist yet, check and if need, create folder for it
  if (!file_exists($config_file)) {
    $config_file_dir = dirname($config_file);
    if (!is_dir($config_file_dir)) {
      mkdir($config_file_dir, 0777, TRUE);
    }
  }
  
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
    return array();
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
 * @param $use_current_dir
 *  Optional. Flag for use current directory for saving config file.
 * 
 * @return
 *  Result of saving function (TRUE if config saved sucessfully).
 */
function set_connection_settings($cid, $connection_settings, $use_current_dir = FALSE) {
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
        $overwrite = readline("Connection '" . $cid . "' already exists, overwrite it? [y/N]: ");
        if ($overwrite == 'y' || $overwrite == 'Y' || $overwrite == 'Yes' || $overwrite == 'yes' || $overwrite == 'YES') {
          $config['connections'][$key] = $connection_settings;
          $connection_exist = TRUE;
        }
        else {
          // canceling
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
 * Check that user input match connection ID in config.
 *
 * @param $input
 *  Some string.
 *
 * @return
 *  Connection ID or FALSE if connetion was not resolved.
 */
function match_cid($input) {
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($input == $cid) {
      return $cid;
    }
  }
  return FALSE;
}

/**
 * Execute a shell command.
 *
 * @param $cmd
 *  Shell command.
 *
 * @param $success_message
 *  Optional. Message for return if executing was successful.
 * 
 * @return
 *  $success_message if executing was successful
 *  or last line of command output if executing failed.
 */
function run_cmd($cmd, $success_message = 'Ok') {
  $output = [];
  $result_code = 0;
  $run = exec($cmd, $output, $result_code);
  if ($result_code == 0) {
    return $success_message;
  }
  // return last line if something goes wrong
  return $run;
}

/**
 * Promt for user input, hide it during typing and then return it.
 * Code taken from: https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
 *
 * @param $prompt
 *  Optional. Message for prompt.
 *
 * @return
 *  User input.
 */
function readline_silent($prompt = '') {
  $command = "/usr/bin/env bash -c 'echo OK'";
  if (rtrim(shell_exec($command)) !== 'OK') {
    trigger_error("Can't invoke bash");
    return;
  }
  $command = "/usr/bin/env bash -c 'read -s -p \""
    . addslashes($prompt)
    . "\" mypassword && echo \$mypassword'";
  $input = rtrim(shell_exec($command));
  echo PHP_EOL;
  return $input;
}

/**
 * Check that user input match connection ID or number
 * in provided connection IDs list.
 *
 * @param $input
 *  Some string.
 *
 * @param $cids
 *  Array of connection IDs.
 * 
 * @return
 *  Connection ID or exit script if connection ID was not resolved.
 */
function validate_input($input, $cids) {
  if (is_numeric($input)) {
    $input = intval($input);
    $cids_count = count($cids);
    if ($input > 0 && $input <= $cids_count) {
      $cid = $cids[$input];
    } else {
      echo $input . ' is not a valid connection number' . PHP_EOL;
      exit(1);
    }
  }
  else {
    if (match_cid($input)) {
      $cid = $input;
    }
    else {
      echo $input . ' is not a valid connection ID' . PHP_EOL;
      exit(1);
    }
  }
  return $cid;
}

function validate_answer_as_connection($answer, $connections_data) {
  $cid = NULL;

  // Answer looks like a number
  if (is_numeric($answer)) {
    $answer = intval($answer);
    foreach ($connections_data as $connection => $connection_data) {
      if ($answer == $connection_data['n']) {
        $cid = $connection_data['id'];
      }
    }
  }
  // Answer looks like a cid
  else {
    foreach ($connections_data as $connection => $connection_data) {
      if ($answer == $connection_data['id']) {
        $cid = $connection_data['id'];
      }
    }
  }

  return $cid;
}

/**
 * Prompt for user input with additional conditions.
 *
 * @param $prompt
 *  Message for prompt.
 *
 * @param $default_value
 *  Optional. Value for empty user input.
 *
 * @param $required
 *  Optional. Flag for repeat prompt until input will be provided.
 *
 * @param $hidden
 *  Optional. Flag for use hidden input.
 *   
 * @return
 *  User input or $default_value if input was empty.
 */
function read_input($prompt, $default_value = NULL, $required = FALSE, $hidden = FALSE) {

  if ($required) {
    $input = '';
    while (!$input) {
      if ($hidden) {
        $input = readline_silent($prompt);
      }
      else {
        $input = readline($prompt);
      }
    }
  }
  else {
    if ($hidden) {
      $input = readline_silent($prompt);
    }
    else {
      $input = readline($prompt);
    }
    if (!$input) {
      $input = $default_value;
    }
  }
  return $input;
}

function get_connections_data($mounted_only = FALSE) {
  $connections = get_connections();
  $mounts = get_mounts();
  $connections_data = [];
  $i = 1;

  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $connections_data[] = array(
        'n' => $i,
        'id' => $cid,
        'title' => $connection_settings['title'],
        'status' => 'Mounted',
      );
      $i++;
    }
    elseif (!$mounted_only) {
      $connections_data[] = array(
        'n' => $i,
        'id' => $cid,
        'title' => $connection_settings['title'],
        'status' => 'Not mounted',
      );
      $i++;
    }
  }

  return $connections_data;
}

function gen_connections_table($connections_data, $output) {
  $table = new Table($output);
  $table->setHeaders(array(
    '#',
    'ID',
    'Title',
    'Status',
  ));

  foreach ($connections_data as $row => $connection_data) {
    if ($connection_data['status'] == 'Mounted') {
      $table->addRow(array(
        '<info>' . $connection_data['n'] . '</info>',
        '<info>' . $connection_data['id'] . '</info>',
        '<info>' . $connection_data['title'] . '</info>',
        '<info>' . $connection_data['status'] . '</info>',
      ));
    }
    else {
      $table->addRow(array(
        $connection_data['n'],
        $connection_data['id'],
        $connection_data['title'],
        $connection_data['status'],
      ));
    }
  }

  $table->setStyle('compact');
  $tableStyle = $table->getStyle();
  $tableStyle->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
  $tableStyle->setCellRowContentFormat('%s  ');
  
  return $table;
}
