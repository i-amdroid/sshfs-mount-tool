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

// @todo detect environment in init()
$environment = 'macos-2.10';
require_once __DIR__ . '/../includes/' . $environment . '.inc';

// Functions

function init() {
  // @todo detect environment
  echo '<Init>' . PHP_EOL;
  return;
}

function create_config_file() {
  // @todo create user config file
  echo '<Create config file>' . PHP_EOL;
  return;
}

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

function match_cid($input) {
  $connections = get_connections();
  foreach ($connections as $cid => $connection_settings) {
    if ($input == $cid) {
      return TRUE;
    }
  }
  return FALSE;
}

function match_cmd($input) {
  global $commands;
  foreach ($commands as $cmd => $command_settings) {
    if (in_array($input, $command_settings['aliases'])) {
      return $cmd;
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

function show_connections($mounted_only = FALSE) {
  $connections = get_connections();
  $mounts = get_mounts();
  $i = 1;
  $cids = [];

  $table = new ConsoleTable();
  $table->setHeaders([
    '#',
    'connection',
    'status',
  ]);
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow([
        green($i),
        green($cid),
        green('mounted'),
      ]);
      $cids[$i] = $cid;
      $i++;
    } elseif (!$mounted_only) {
      $table->addRow([
        $i,
        $cid,
        'not mounted',
      ]);
      $cids[$i] = $cid;
      $i++;
    }
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();
  return $cids;
}

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
      echo $input . ' is not a valid connection name' . PHP_EOL;
      exit(1);
    }
  }
  return $cid;
}

// User input commands

$commands = [];

$commands['default'] = [
  'name' => 'Mount connection',
  'aliases' => [],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
    'password' => [
      'key' => 'p',
    ],
  ],
  'cmd' => 'cmd_mount', 
];

$commands['unmount'] = [
  'name' => 'Unmount connection',
  'aliases' => [
    'unmount',
    'um',
  ],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
  ],
  'cmd' => 'cmd_unmount', 
];

$commands['add'] = [
  'name' => 'Add connection',
  'aliases' => [
    'add',
  ],
  'cmd' => 'cmd_add', 
];

$commands['remove'] = [
  'name' => 'Remove connection',
  'aliases' => [
    'remove',
    'rm',
  ],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
  ],
  'cmd' => 'cmd_remove', 
];

$commands['list'] = [
  'name' => 'List connection properties',
  'aliases' => [
    'list',
    'ls',
  ],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
  ],
  'cmd' => 'cmd_list', 
];

$commands['status'] = [
  'name' => 'Show status of connections',
  'aliases' => [
    'status',
    'st',
  ],
  'cmd' => 'cmd_status', 
];

$commands['config'] = [
  'name' => 'Open config file',
  'aliases' => [
    'config',
    'cfg',
  ],
  'flags' => [
      'global' => [
        'key' => 'g',
    ],
  ],
  'cmd' => 'cmd_config',
];

$commands['help'] = [
  'name' => 'Show help',
  'aliases' => [
    'help',
    '--help',
    '-h',
  ],
  'optional_args' => [
    'cmd' => [
      'validate' => 'match_cmd',
    ],
  ],
  'cmd' => 'cmd_help', 
];

$commands['version'] = [
  'name' => 'Show version',
  'aliases' => [
    'version',
    '--version',
    '-v',
  ],
  'cmd' => 'cmd_version',
];

$commands['info'] = [
  'name' => 'Show information about dependencies',
  'aliases' => [
    'info',
    '--info',
    '-i',
  ],
  'cmd' => 'cmd_info',
];

$commands['cd'] = [
  'name' => 'Change directory to connection mount directory',
  'aliases' => [
    'cd',
  ],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
  ],
  'cmd' => 'cmd_cd', 
];

$commands['ssh'] = [
  'name' => 'Launch SSH session',
  'aliases' => [
    'ssh',
  ],
  'optional_args' => [
    'cid' => [
      'validate' => 'match_cid',
    ],
  ],
  'cmd' => 'cmd_ssh', 
];


function cmd_mount($cid = NULL, $password = NULL) {
  if (!$cid) {
    $cids = show_connections();
    $input = readline('Number or name of connection for mount: ');
    $cid = validate_input($input, $cids);
  }
  $cmd = gen_mount_cmd($cid);
  $success_message = 'Mounted' . PHP_EOL;
  echo run_cmd($cmd, $success_message);
  return;
}

function cmd_unmount($cid = FALSE) {
  if (!$cid) {
    $cids = show_connections(TRUE);
    $input = readline('Number or name of connection for unmount: ');
    $cid = validate_input($input, $cids);
  }
  $cmd = gen_unmount_cmd($cid);
  $success_message = 'Unmounted' . PHP_EOL;
  echo run_cmd($cmd, $success_message);
  return;
}

function cmd_add() {
  // @todo 
  echo '<Add connection>' . PHP_EOL;
  return;
}

function cmd_remove($cid = FALSE) {
  // @todo 
  echo '<Remove connection> cid: ' . $cid . PHP_EOL;
  return;
}

function cmd_list($cid = FALSE) {
  if (!$cid) {
    $cids = show_connections();
    $input = readline('Number or name of connection to show: ');
    $cid = validate_input($input, $cids);
  }
  $connection_settings = get_connection_settings($cid);
  // @todo pretify
  print_r ($connection_settings);
  return;
}

function cmd_cd($cid = FALSE) {
  // @todo 
  echo '<Changing directory> cid: ' . $cid . PHP_EOL;
  return;
}

function cmd_ssh($cid = FALSE) {
  // @todo 
  echo '<Launch SSH session> cid: ' . $cid . PHP_EOL;
  return;
}

function cmd_status() {
  show_connections();
  return;
}

function cmd_config($global = FALSE) {
  // @todo 
  echo '<Open config file> global: ' . $global . PHP_EOL;
  return;
}

function cmd_help($cmd = FALSE) {
  // @todo 
  echo '<Show help> cmd: ' . $cmd . PHP_EOL;
  return;
}

function cmd_version() {
  global $project_info_file;
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  echo $project_info['version'] . PHP_EOL;
  return;
}

function cmd_info() {
  global $project_info_file;
  $info = [];
  $project_info = file_get_contents($project_info_file);
  $project_info = json_decode($project_info, true);
  $info[] = 'SSHFS Mount Tool v' . $project_info['version'];
  exec('sshfs --version 2> /dev/null', $info);
  // @todo check for other dependencies
  // @todo show as table "dependency version : status"
  foreach ($info as $key => $line) {
    echo $line . PHP_EOL;
  }
  return;
}

function handle_input($args, $args_count) {
  global $commands;
  // no args
  if ($args_count == 1) {
    // analise skip for performance
    $cmd_cmd = $commands['default']['cmd'];
    $cmd_cmd();
    return;
  }
  // one arg
  elseif ($args_count == 2) {
    // one arg: cmd
    $cmd = match_cmd($args[1]);
    if ($cmd) {
      $cmd_cmd = $commands[$cmd]['cmd'];
      $cmd_cmd();
    }
    // one arg: cid, analise skip for performance
    elseif (match_cid($args[1])) {
      $cmd_cmd = $commands['default']['cmd'];
      $cmd_cmd($args[1]);
    }
    // one arg: not match
    else {
      echo 'Unknown command ' . $args[1] . PHP_EOL;
    }
  }
  // two+ args
  elseif ($args_count >= 2) {
    // two+ args: cmd, something
    $cmd = match_cmd($args[1]);
    if ($cmd) {
      // check command have required args - skip for now
      // check command have optional args
      if (array_key_exists('optional_args', $commands[$cmd])) {
        // get defenition of first arg
        $cmd_arg_1 = reset($commands[$cmd]['optional_args']);
        // check for validation
        if (array_key_exists('validate', $cmd_arg_1)) {
          $cmd_arg_validate = $cmd_arg_1['validate'];
          // valid
          if ($cmd_arg_validate($args[2])) {
            // @todo check for other agrs
            $cmd_cmd = $commands[$cmd]['cmd'];
            $cmd_cmd($args[2]);
          }
          // not valid
          else {
            echo 'Argument ' . $args[2] . ' not valid for ' . $cmd . PHP_EOL;
          }
        }
        // no validation
        else {
          // @todo check for other agrs
          $cmd_cmd = $commands[$cmd]['cmd'];
          $cmd_cmd($args[2]);
        }
        
      }
      // check command have flags
      elseif (array_key_exists('flags', $commands[$cmd])) {

      }
      // too many args
      else {
        echo 'Too many arguments for ' . $args[1] . PHP_EOL;
      }


    }

    // two+ args: cid, something

    // two+ args: not mach
  }
}

// Main function

handle_input($argv, $argc);

