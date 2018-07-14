<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

// Variables

$home = $_SERVER['HOME'];
$path = exec('pwd');

$user_config_file = $home . '/.config/smt/smt.yml';
$current_config_file = $path . '/smt.yml';

$project_info_file = __DIR__ . '/../composer.json';

// Functions

function get_config_file() {
  global $user_config_file;
  global $current_config_file;
  if (file_exists($current_config_file)) {
    return $current_config_file;
  }
  return $user_config_file;
}

function get_config() {
  $config_file = get_config_file();
  return Yaml::parseFile($config_file);
}

function set_config($config, $config_file) {
  $yaml = Yaml::dump($config);
  file_put_contents($config_file, $yaml);
}

function get_preferences() {
  $config = get_config();
  return $config['preferences'];
}

function get_connections() {
  $config = get_config();
  return $config['connections'];
}

function get_connection_settings($cid) {
  $config = get_config();
  return $config['connections'][$cid];
}

function set_connection_settings($cid, $connection_settings, $use_current_config_file = FALSE) {
  global $user_config_file;
  global $current_config_file;
  $config = get_config();
  $connection_exist = FALSE;
  foreach ($config['connections'] as $key => $value) {
    if ($key == $cid) {
      // @todo ask for rewrite
      $config['connections'][$key] = $connection_settings;
      $connection_exist = TRUE;
    }
  }
  if (!$connection_exist) {
    $config['connections'][$cid] = $connection_settings;
  }
  if ($use_current_config_file) {
    $config_file = $current_config_file;
  } else {
    $user_config_file;
  }
  return set_config($config, $config_file);
}

function remove_connection_settings($cid) {
  // @todo
}

function gen_mount_cmd($cid) {
  $preferences = get_preferences();
  $options = $preferences['options'];
  $connection_settings = get_connection_settings($cid);
  $cmd = [];

  if ($connection_settings['password']) {
    $cmd[] = 'echo ' . $connection_settings['password'] . ' |';
    $options[] = 'password_stdin';
  }
  if ($connection_settings['key']) {
    $options[] = 'IdentityFile=' . $connection_settings['key'];
  }
  array_unshift($options, 'volname=' . $connection_settings['title']);
  $cmd[] = $preferences['mount_cmd'];
  if ($connection_settings['user']) {
    $cmd[] = $connection_settings['user'] . '@' . $connection_settings['server'] . ':' . $connection_settings['remote'];
  } else {
    $cmd[] = $connection_settings['server'] . ':' . $connection_settings['remote'];
  }
  $cmd[] = $connection_settings['mount'];
  $cmd[] = '-o ' . implode(',', $options);
  if ($connection_settings['port']) {
    $cmd[] = '-p ' . $connection_settings['port'];
  };
  return implode(' ', $cmd);
}

function gen_unmount_cmd($cid) {
  $preferences = get_preferences();
  $connection_settings = get_connection_settings($cid);
  return $preferences['unmount_cmd'] . ' ' . $connection_settings['mount'];
}

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

// User input commands

// choose connection for mount
function cmd_choose_mount_connection() {
  $connections = get_connections();
  $i = 1;
  $cids = [];
  echo "Choose connection settings for mount:\n";
  foreach ($connections as $cid => $connection_settings) {
    echo $i . ': ' . $cid . "\n";
    $cids[$i] = $cid;
    $i++;
  }
  $input = readline('Number or name of connection: ');
  if (is_numeric($input)) {
    // @todo check for correct number
    $cid = $cids[$input];
  }
  else {
    // @todo check for correct name
    $cid = $input;
  }
  cmd_mount($cid);
}

function cmd_mount($cid, $password = NULL) {
  // echo '<Mounting ' . $cid . ">\n";
  // $connection_settings = get_connection_settings($cid);
  // print_r ($connection_settings);
  $cmd = gen_mount_cmd($cid);
 // echo $cmd;
  $success_message = "Mounted\n";
  echo run_cmd($cmd, $success_message);
}

// choose connection for unmount
function cmd_choose_unmount_connection() {

}

function cmd_unmount($cid) {
  $cmd = gen_unmount_cmd($cid);
  //echo $cmd;
  $success_message = "Unmounted\n";
  echo run_cmd($cmd, $success_message);
}

function cmd_init() {
  // @todo create user config file
  echo "<Creating config file>\n";
}

function cmd_info() {
  global $project_info_file;
  $info = [];
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  $info[] = 'SSHFS Mount Tool v' . $project_info['version'];
  exec('sshfs --version', $info);
  foreach ($info as $key => $line) {
    echo $line . "\n";
  }
}

function cmd_unknown($cmd) {
  echo 'Unknown argument: ' . $cmd . "\n";
}

function select_command($cmd, $args = NULL) {
  switch ($cmd) {
    case 'cc':
      cmd_choose_mount_connection();
      break;

    case 'mnt':
    case 'mount':
      cmd_mount($args['cid'], $args['password']);
      break;

    case 'um':
    case 'unmount':
      cmd_unmount($args['cid']);
      break;  

    case 'init':
      cmd_init();
      break;

    case 'info':
      cmd_info();
      break;  
    
    default:
      cmd_unknown($cmd);
      break;
  }
}

function parse_input($argv, $argc) {
  $args = [];
  if ($argc == 1) {
    select_command('cc');
  } elseif ($argc == 2) {
    select_command($argv[1]);
  } else {
    $args['cid'] = $argv[2];
    $args['password'] = NULL;
    select_command($argv[1], $args);
  }
}

// Main function

parse_input($argv, $argc);
