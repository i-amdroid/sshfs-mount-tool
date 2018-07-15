<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use LucidFrame\Console\ConsoleTable;

// Variables

$home = $_SERVER['HOME'];
$path = exec('pwd');

$user_config_file = $home . '/.config/smt/smt.yml';
$current_config_file = $path . '/smt.yml';

$project_info_file = __DIR__ . '/../composer.json';

// @todo detect environment
$environment = 'macos-2.10';
require_once __DIR__ . '/../includes/' . $environment . '.inc';

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

function get_cid($mount_point) {
  global $home;
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($mount_point == $connection_settings['mount']) {
      return $cid;
    } elseif (substr($connection_settings['mount'], 0, 1) == '~') {
      $absolute_path = $home . substr($connection_settings['mount'], 1);
      if ($mount_point == $absolute_path) {
        return $cid;
      }
    }
  }
  return FALSE;
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

function green($text) {
  return "\033[32m" . $text . "\033[39m";
}

// User input commands

// choose connection for mount
function cmd_choose_mount_connection() {
  $connections = get_connections();
  $mounts = get_mounts();
  $i = 1;
  $cids = [];

  $table = new ConsoleTable();
  $table->setHeaders(array('#', 'connection', 'status'));
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow(array(
        green($i),
        green($cid),
        green('mounted')
      ));
    } else {
      $table->addRow(array($i, $cid, 'not mounted'));
    }
    $cids[$i] = $cid;
    $i++;
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();
  $input = readline('Number or name of connection for mount: ');
  if (is_numeric($input)) {
    // @todo check for correct number
    $cid = $cids[$input];
  }
  else {
    // @todo check for correct name
    $cid = $input;
  }
  return cmd_mount($cid);
}

function cmd_mount($cid, $password = NULL) {
  // echo '<Mounting ' . $cid . PHP_EOL;
  // $connection_settings = get_connection_settings($cid);
  // print_r ($connection_settings);
  $cmd = gen_mount_cmd($cid);
 // echo $cmd;
  $success_message = 'Mounted' . PHP_EOL;
  echo run_cmd($cmd, $success_message);
}

// choose connection for unmount
function cmd_choose_unmount_connection() {
  $connections = get_connections();
  $mounts = get_mounts();
  $i = 1;
  $cids = [];

  $table = new ConsoleTable();
  $table->setHeaders(array('#', 'connection', 'status'));
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow(array(
        green($i),
        green($cid),
        green('mounted')
      ));
      $cids[$i] = $cid;
      $i++;
    }
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();
  $input = readline('Number or name of connection for unmount: ');
  if (is_numeric($input)) {
    // @todo check for correct number
    $cid = $cids[$input];
  }
  else {
    // @todo check for correct name
    $cid = $input;
  }
  return cmd_unmount($cid);
}

function cmd_unmount($cid) {
  $cmd = gen_unmount_cmd($cid);
  //echo $cmd;
  $success_message = 'Unmounted' . PHP_EOL;
  echo run_cmd($cmd, $success_message);
}

function cmd_init() {
  // @todo create user config file
  echo '<Creating config file>' . PHP_EOL;
}

function cmd_info() {
  global $project_info_file;
  $info = [];
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  $info[] = 'SSHFS Mount Tool v' . $project_info['version'];
  exec('sshfs --version', $info);
  foreach ($info as $key => $line) {
    echo $line . PHP_EOL;
  }
}

function cmd_list($cid) {
  $connection_settings = get_connection_settings($cid);
  // @todo pretify
  print_r ($connection_settings);
}

function cmd_status() {
  $connections = get_connections();
  $mounts = get_mounts();
  $i = 1;
  $table = new ConsoleTable();
  $table->setHeaders(array('#', 'connection', 'status'));
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow(array(
        green($i),
        green($cid),
        green('mounted')
      ));
    } else {
      $table->addRow(array($i, $cid, 'not mounted'));
    }
    $i++;
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();
  return;
}

function cmd_unknown($cmd) {
  echo 'Unknown argument: ' . $cmd . PHP_EOL;
}

function select_command($cmd, $args = NULL) {
  switch ($cmd) {
    case 'ccm':
      cmd_choose_mount_connection();
      break;

    case 'mnt':
    case 'mount':
      cmd_mount($args['cid'], $args['password']);
      break;

    case 'ccu':
      cmd_choose_unmount_connection();
      break;

    case 'um':
    case 'unmount':
      cmd_unmount($args['cid']);
      break;  

    case 'init':
      cmd_init();
      break;

    case 'ls':
    case 'list':
      cmd_list($args['cid']);
      break;  

    case 'st':
    case 'status':
      cmd_status();
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
    select_command('ccm');
  } elseif ($argc == 2) {
    if ($argv[1] == 'um') {
      select_command('ccu');
    } else {
      select_command($argv[1]);
    }
  } else {
    $args['cid'] = $argv[2];
    $args['password'] = NULL;
    select_command($argv[1], $args);
  }
}

// Main function

parse_input($argv, $argc);
