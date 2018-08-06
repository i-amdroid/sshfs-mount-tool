<?php

/**
 * @file
 * Provides main functionality.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use LucidFrame\Console\ConsoleTable;

$preferences['home_path'] = $_SERVER['HOME'];
$preferences['current_path'] = exec('pwd');

$preferences['user_config_file'] = $preferences['home_path'] . '/.config/smt/smt.yml';
$preferences['current_config_file'] = $preferences['current_path'] . '/smt.yml';
$preferences['project_info_file'] = __DIR__ . '/../composer.json';

$preferences['os_functions_inc'] = init();
require_once __DIR__ . '/../includes/' . $preferences['os_functions_inc'];

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
 *  TRUE if config saved successfully.
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
 *  Result of saving function (TRUE if config saved successfully).
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
 *  Connection ID or FALSE if connection was not resolved.
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
 * Check that user input match command name.
 *
 * @param $input
 *  Some string.
 *
 * @return
 *  Command name or FALSE if command was not resolved.
 */
function match_cmd($input) {
  global $commands;
  foreach ($commands as $cmd => $command_settings) {
    if (in_array($input, $command_settings['aliases'])) {
      return $cmd;
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
 * Prompt for user input, hide it during typing and then return it.
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
 * Add special symbols to make text color green in terminal.
 *
 * @param $text
 *  Some text.
 *
 * @return
 *  Text wrapped by special symbols.
 */
function green($text) {
  return "\033[32m" . $text . "\033[39m";
}

/**
 * Choose connection from config and return it.
 * If only one connection exist, it will be returned automatically.
 * If multiple connections exists, user will prompted to choose one. 
 *
 * @param $mounted_only
 *  Optional. Flag for show only mounted connections in list.
 *
 * @param $show_only
 *  Optional. Flag for only showing connections list without prompting for choose.
 *
 * @param $silent
 *  Optional. Flag for silent mode.
 *  
 * @return
 *  Connection ID.
 */
function choose_connection($mounted_only = FALSE, $show_only = FALSE, $silent = FALSE) {
  $connections = get_connections();

  // no connections
  if (empty($connections)) {
    if (!$silent) {
      echo 'No saved connections' . PHP_EOL;
    }
    // not error
    exit (0);
  }

  // one connection, not just show
  if (count($connections) == 1 && !$show_only) {
    return key($connections);
  }

  $mounts = get_mounts();

  // no mounts
  if ($mounted_only) {
    if (empty($mounts)) {
      if (!$silent) {
        echo 'No mounted connections' . PHP_EOL;
      }
      // not error
      exit (0);
    }
  }

  // one mount, should work automatically only when exist only one connection in config file
  if ($mounted_only && count($connections) == 1) {
    if (count($mounts) == 1) {
      return $mounts[0];
    }
  }

  // multiple connections
  $i = 1;
  $cids = [];

  $table = new ConsoleTable();
  $table->setHeaders([
    '#',
    'id',
    'title',
    'status',
  ]);
  foreach ($connections as $cid => $connection_settings) {
    if (in_array($cid, $mounts)) {
      $table->addRow([
        green($i),
        green($cid),
        green($connection_settings['title']),
        green('mounted'),
      ]);
      $cids[$i] = $cid;
      $i++;
    }
    elseif (!$mounted_only) {
      $table->addRow([
        $i,
        $cid,
        $connection_settings['title'],
        'not mounted',
      ]);
      $cids[$i] = $cid;
      $i++;
    }
  }
  $table->setPadding(2);
  $table->hideBorder();
  $table->display();

  if (!$show_only) {
    $input = readline('Number or ID of connection: ');
    $cid = validate_input($input, $cids);
    return $cid;
  }

  return;
}

/**
 * Show connection settings in table format.
 *
 * @param $cid
 *  Connection ID.
 *
 * @param $connection_settings
 *  Array of settings.
 *
 * @return
 *  Nothing.
 */
function show_connection_settings($cid, $connection_settings) {
  $table = new ConsoleTable();
  $table->setHeaders([
    ' property',
    ':',
    'value ',
  ]);
  $table->addRow([
    ' id',
    ':',
    $cid . ' ',
  ]);
  foreach ($connection_settings as $key => $value) {
    if ($key == 'options') {
      $table->addRow([
        ' ' . $key,
        ':',
        implode(',', $value) . ' ',
      ]);
    }
    elseif ($key == 'password' && $value) {
      $table->addRow([
        ' ' . $key,
        ':',
        '[password] ',
      ]);
    }
    else {
      $table->addRow([
        ' ' . $key,
        ':',
        $value . ' ',
      ]);
    }
  }
  $table->setPadding(1);
  $table->hideBorder();
  $table->display();

  return;
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

/**
 * Definition of parameters for command functions.
 *
 * arguments - values for command functions.
 * options - flags for altering command functions behaviour.
 * flags - flags with value for providing additional parameters to command functions.
 *
 * This array used for resolving command line arguments
 * and generating help.
 */
$params = [];

$params['arguments']['cid'] = [
  'type' => 'argument',
  'name' => 'Connection ID',
  'argument' => 'connection id',
  'validate' => 'match_cid',
];

$params['arguments']['cmd'] = [
  'type' => 'argument',
  'name' => 'Command',
  'argument' => 'command',
  'validate' => 'match_cmd',
];

// handled separately, listed here for generating help and pass validation as option
$params['options']['global'] = [
  'type' => 'option',
  'name' => 'Use config from user directory',
  'key' => 'g',
];

$params['options']['verbose'] = [
  'type' => 'option',
  'name' => 'Verbose mode',
  'key' => 'v',
];

$params['options']['silent'] = [
  'type' => 'option',
  'name' => 'Silent mode',
  'key' => 's',
];

/*
$params['options']['yes'] = [
  'type' => 'option',
  'name' => 'Automatic confirmation',
  'key' => 'y',
];
*/

// listed here for generating help and pass validation as option
$params['options']['help'] = [
  'type' => 'option',
  'name' => 'Show help',
  'key' => 'h',
];

$params['flags']['password'] = [
  'type' => 'flag',
  'name' => 'Provide password',
  'argument' => 'password',
  'key' => 'p',
];

/**
 * Definition of commands.
 *
 * name - Command name.
 * aliases - list of aliases.
 * args - list of required arguments
 * optional_args - list of optional arguments.
 * cms - command function.
 *
 * This array used for resolving commands 
 * and generating help.
 */
$commands = [];

$commands['mount'] = [
  'name' => 'Mount connection',
  'aliases' => [
    'mount',
  ],
  'optional_args' => [
    'global' => $params['options']['global'],
    'verbose' => $params['options']['verbose'],
    'silent' => $params['options']['silent'],
    'cid' => $params['arguments']['cid'],
    'password' => $params['flags']['password'],
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
    'global' => $params['options']['global'],
    'verbose' => $params['options']['verbose'],
    'silent' => $params['options']['silent'],
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_unmount', 
];

$commands['add'] = [
  'name' => 'Add connection',
  'aliases' => [
    'add',
  ],
  'optional_args' => [
    'verbose' => $params['options']['verbose'],
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
    'global' => $params['options']['global'],
    'silent' => $params['options']['silent'],
    // 'yes' => $params['options']['yes'],
    'cid' => $params['arguments']['cid'],
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
    'global' => $params['options']['global'],
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_list', 
];

$commands['status'] = [
  'name' => 'Show status of connections',
  'aliases' => [
    'status',
    'st',
  ],
  'optional_args' => [
    'global' => $params['options']['global'],
  ],
  'cmd' => 'cmd_status', 
];

$commands['config'] = [
  'name' => 'Open config file',
  'aliases' => [
    'config',
    'cfg',
  ],
  'optional_args' => [
    'global' => $params['options']['global'],
  ],
  'cmd' => 'cmd_config',
];

$commands['help'] = [
  'name' => 'Show help',
  'aliases' => [
    'help',
    '-h',
    '--help',
  ],
  'optional_args' => [
    'cmd' => $params['arguments']['cmd'],
  ],
  'cmd' => 'cmd_help', 
];

$commands['version'] = [
  'name' => 'Show version',
  'aliases' => [
    'version',
    '-V',
    '--version',
  ],
  'cmd' => 'cmd_version',
];

$commands['info'] = [
  'name' => 'Show information about dependencies',
  'aliases' => [
    'info',
    '-i',
    '--info',
  ],
  'cmd' => 'cmd_info',
];

$commands['cd'] = [
  'name' => 'Change directory to connection mount directory',
  'aliases' => [
    'cd',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_cd', 
];

$commands['ssh'] = [
  'name' => 'Launch SSH session',
  'aliases' => [
    'ssh',
  ],
  'optional_args' => [
    'cid' => $params['arguments']['cid'],
  ],
  'cmd' => 'cmd_ssh', 
];

/**
 * Mount connection. Default function.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_mount($args) {
  global $preferences;
  $silent = (isset($args['silent'])) ? true : false;
  $verbose = (isset($args['verbose'])) ? true : false;

  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(FALSE, FALSE, $silent);
  }
  if (isset($args['password'])) {
    $password = $args['password'];
  }
  else {
    $password = FALSE;
  }

  $cmd = gen_mount_cmd($cid, $password);
  $connection_settings = get_connection_settings($cid);

  // check existing of mount point and create if needed
  if (substr($connection_settings['mount'], 0, 1) == '~') {
    $mount_dir = $preferences['home_path'] . substr($connection_settings['mount'], 1);
  }
  else {
    $mount_dir = $connection_settings['mount'];
  }
  if (!is_dir($mount_dir)) {
    mkdir($mount_dir, 0777, TRUE);
  }

  $success_message = '';
  if (isset($connection_settings['user'])) {
    $success_message .= $connection_settings['user'] . '@';
  }
  $success_message .= $connection_settings['server'] . ' ' . green('mounted') . ' to ' . $connection_settings['mount'] . PHP_EOL;
  if (!$silent) {
    if ($verbose) {
      $masked_cmd = gen_mount_cmd($cid, $password, TRUE);
      echo $masked_cmd . PHP_EOL;
    }
  }
  $run = run_cmd($cmd, $success_message);
  if (!$silent) {
    echo $run;
  }

  exit(0);
}

/**
 * Unmount connection.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_unmount($args) {

  $silent = (isset($args['silent'])) ? true : false;
  $verbose = (isset($args['verbose'])) ? true : false;

  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(TRUE, FALSE, $silent);
  }

  $cmd = gen_unmount_cmd($cid);
  $connection_settings = get_connection_settings($cid);
  $success_message = '';
  if (isset($connection_settings['user'])) {
    $success_message .= $connection_settings['user'] . '@';
  }
  $success_message .= $connection_settings['server'] . ' ' . green('unmounted') . PHP_EOL;
  if (!$silent) {
    if ($verbose) {
      echo $cmd . PHP_EOL;
    }
  }
  $run = run_cmd($cmd, $success_message);
  if (!$silent) {
    echo $run;
  }

  exit(0);
}

/**
 * Add new connection.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_add($args) {
  global $preferences;
  $silent = (isset($args['silent'])) ? true : false;
  $verbose = (isset($args['verbose'])) ? true : false;

  $connection_settings = [];
  $prompt_server = ($verbose) ? 'Server (required): ' : 'Server: ';
  $connection_settings['server'] = read_input($prompt_server, NULL, TRUE);

  $prompt_port = ($verbose) ? 'Port. Default "22": ' : 'Port: ';
  $connection_settings['port'] = read_input($prompt_port);

  $connection_settings['user'] = read_input('Username: ');

  $prompt_password = ($verbose) ? 'Password. Input hidden. If password not provided, it will be asked every time on connect. Leave blank for key auth: ' : 'Password: ';
  $connection_settings['password'] = read_input($prompt_password, NULL, FALSE, TRUE);

  $prompt_key = ($verbose) ? 'Path to key file. Usually "~/.ssh/id_rsa". Leave blank for password auth: ' : 'Path to key file: ';
  $connection_settings['key'] = read_input($prompt_key);

  // try to suggest default connection tile
  if (ip2long($connection_settings['server']) !== FALSE) {
    $default_title = str_replace('.', '-', $connection_settings['server']);
  }
  else {
    // 'server' is domain name
    $domain = explode('.', $connection_settings['server']);
    if (count($domain) > 1) {
      // domain without zone
      $default_title =  $domain[count($domain) - 2];
    }
    else {
      // complex domain name, so just print full domain
      $default_title = $connection_settings['server'];
    }
  }
  $default_mount = '~/mnt/' . $default_title;
  $prompt_mount = ($verbose) ? 'Mount directory. Required for mounting. [Enter] - "' . $default_mount . '": ' : 'Mount directory: ';
  $connection_settings['mount'] = read_input($prompt_mount, $default_mount);

  $connection_settings['remote'] = read_input('Remote directory: ');
 
  $prompt_options = ($verbose) ? 'Mount options, separated by comma: ' : 'Mount options : ';
  $options = read_input($prompt_options, '');
  $options = explode (',', $options);
  $options = array_map('trim', $options);
  $connection_settings['options'] = array_filter($options);

  $prompt_title = ($verbose) ? 'Connection title [Enter] - "' . $default_title . '": ' : 'Connection title: ';
  
  $title = read_input($prompt_title, $default_title);
  // add title to the begining of the list
  $connection_settings = array('title' => $title) + $connection_settings;

  $prompt_cid = ($verbose) ? 'Connection ID. Used as shortcut. [Enter] - "' . $default_title . '": ' : 'Connection ID: ';
  $cid = read_input($prompt_cid, $default_title);

  if ($verbose) {
    echo PHP_EOL;
    show_connection_settings($cid, $connection_settings);
    echo PHP_EOL;
  }

  // @todo while loop
  $save_config = readline('Seve config? [y, Enter] - yes, to user directory / [c] - to current directory / [n] - cancel: ');
  if (!$save_config || $save_config == 'y' || $save_config == 'Y' || $save_config == 'Yes' || $save_config == 'yes' || $save_config == 'YES') {
    $preferences['global'] = TRUE;
    set_connection_settings($cid, $connection_settings);
  }
  elseif ($save_config == 'c' || $save_config == 'C') {
    $preferences['global'] = FALSE;
    set_connection_settings($cid, $connection_settings, TRUE);
  }
  else {
    // canceling
    exit(0);
  }
  if (!$silent) {
    // here can be only success savings
    echo 'Connection saved.' . PHP_EOL;
  }
  exit(0);
}

/**
 * Remove existing connection.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_remove($args) {

  $silent = (isset($args['silent'])) ? true : false;
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(FALSE, FALSE, $silent);
  }

  remove_connection_settings($cid);
  if (!$silent) {
    // here can be only success removing
    echo 'Connection removed.' . PHP_EOL;
  }
  exit(0);
}

/**
 * Show settings of a connection.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_list($args) {

  $silent = (isset($args['silent'])) ? true : false;
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(FALSE, FALSE, $silent);
  }

  $connection_settings = get_connection_settings($cid);
  show_connection_settings($cid, $connection_settings);
  exit(0);
}

/**
 * Go to connection mount directory.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_cd($args) {

  global $preferences;
  $silent = (isset($args['silent'])) ? true : false;
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(FALSE, FALSE, $silent);
  }

  $connection_settings = get_connection_settings($cid);
  if (isset($connection_settings['mount'])) {
    if (substr($connection_settings['mount'], 0, 1) == '~') {
      $path = $preferences['home_path'] . substr($connection_settings['mount'], 1);
    }
    else {
      $path = $connection_settings['mount'];
    }
    $cd_cmd = 'cd ' . $path;
    run_terminal_cmd($cd_cmd);
    exit(0);
  }
  else {
    echo 'No mount point for ' . $cid .  ' set' . PHP_EOL;
    exit(1);
  }
}

/**
 * Launch SSH session for a connection.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_ssh($args) {

  $silent = (isset($args['silent'])) ? true : false;
  if (isset($args['cid'])) {
    $cid = $args['cid'];
  }
  else {
    $cid = choose_connection(FALSE, FALSE, $silent);
  }

  $connection_settings = get_connection_settings($cid);

  $ssh_cmd = 'ssh ';
  if (isset($connection_settings['user'])) {
    $ssh_cmd .= $connection_settings['user'] . '@';
  }
  $ssh_cmd .= $connection_settings['server'];
  run_terminal_cmd($ssh_cmd);

  exit(0);
}

/**
 * Show mount status of all connections.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_status($args) {

  $silent = (isset($args['silent'])) ? true : false;
  choose_connection(FALSE, TRUE, $silent);

  exit(0);
}

/**
 * Open config file for edit.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_config($args) {
  global $preferences;
  $config_file = get_config_file();
  $config_cmd = $preferences['editor'] . ' ' . $config_file . ' > `tty`';
  system($config_cmd);
  exit(0);
}

/**
 * Show help.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_help($args) {
  global $commands;
  global $params;
  if (isset($args['cmd'])) {
    $cmd = $args['cmd'];

    // help for the command
    foreach ($commands as $command => $command_properties) {
      if ($cmd == $command) {
        $usage = [];
        $has_options = FALSE;
        $has_argument = FALSE;
        $has_flags = FALSE;
        if (isset($command_properties['optional_args'])) {
          foreach ($command_properties['optional_args'] as $arg => $arg_properties) {
            switch ($arg_properties['type']) {
              case 'option':
                $has_options = TRUE;
                break;
              case 'argument':
                $has_argument = TRUE;
                break;
              case 'flag':
                $has_flags = TRUE;
                break;
            }
          }
        }
        echo $command_properties['name'] . PHP_EOL;
        echo PHP_EOL;
        echo 'Usage: ' . PHP_EOL;
        if ($has_options) {
          $usage[] = '[options]';
        }
        if ($has_argument) {
          $usage[] = '[argument]';
        }
        if ($has_flags) {
          $usage[] = '[flags]';
        }

        foreach ($command_properties['aliases'] as $alias) {
          echo '  smt ' . $alias . ' ' . implode(' ', $usage) . PHP_EOL;
          if ($alias == 'mount') {
            echo '  smt ' . implode(' ', $usage) . PHP_EOL;
          }
        }

        if ($has_options) {
          echo PHP_EOL;
          echo 'Options: ' . PHP_EOL;
          $opt_table = new ConsoleTable();
          foreach ($command_properties['optional_args'] as $opt => $opt_properties) {
            if ($opt_properties['type'] == 'option') {
              $aliases = '-' . $opt_properties['key'] . ', --' . $opt;
              $opt_table->addRow([
                $aliases,
                $opt_properties['name'],
              ]);
            }
          }
          $opt_table->setPadding(2);
          $opt_table->hideBorder();
          $opt_table->display();
        }

        if ($has_argument) {
          echo PHP_EOL;
          echo 'Argument: ' . PHP_EOL;
          $arg_table = new ConsoleTable();
          foreach ($command_properties['optional_args'] as $arg => $arg_properties) {
            if ($arg_properties['type'] == 'argument') {
              $aliases = '<' . $arg_properties['argument'] . '>';
              $arg_table->addRow([
                $aliases,
                $arg_properties['name'],
              ]);
            }
          }
          $arg_table->setPadding(2);
          $arg_table->hideBorder();
          $arg_table->display();
        }

        if ($has_flags) {
          echo PHP_EOL;
          echo 'Flags: ' . PHP_EOL;
          $flg_table = new ConsoleTable();
          foreach ($command_properties['optional_args'] as $flg => $flg_properties) {
            if ($flg_properties['type'] == 'flag') {
              $aliases = '-' . $flg_properties['key'] . ', --' . $flg . ' <' . $flg_properties['argument'] . '>';
              $flg_table->addRow([
                $aliases,
                $flg_properties['name'],
              ]);
            }
          }
          $flg_table->setPadding(2);
          $flg_table->hideBorder();
          $flg_table->display();
        }

      }
    }

  }
  else {

    // help for all commands
    echo 'SSHFS Mount Tool'. PHP_EOL;
    echo 'Tool for manage and mount SSH connections as file system volumes.' . PHP_EOL;
    echo PHP_EOL;
    echo 'Usage: ' . PHP_EOL;
    echo '  smt <command> [options] [argument] [flags]' . PHP_EOL;
    echo PHP_EOL;
    echo 'Commands: ' . PHP_EOL;
    $cmd_table = new ConsoleTable();
    foreach ($commands as $command => $command_properties) {
      $aliases = implode(', ', $command_properties['aliases']);
      if ($command == 'mount') {
        $aliases = "'empty command', " . $aliases;
      }
      $cmd_table->addRow([
        $aliases,
        $command_properties['name'],
      ]);
    }
    $cmd_table->setPadding(2);
    $cmd_table->hideBorder();
    $cmd_table->display();

    echo PHP_EOL;
    echo 'Options: ' . PHP_EOL;
    $opt_table = new ConsoleTable();
    foreach ($params['options'] as $opt => $opt_properties) {
      $aliases = '-' . $opt_properties['key'] . ', --' . $opt;
      $opt_table->addRow([
        $aliases,
        $opt_properties['name'],
      ]);
    }
    $opt_table->setPadding(2);
    $opt_table->hideBorder();
    $opt_table->display();

    echo PHP_EOL;
    echo 'Arguments: ' . PHP_EOL;
    $arg_table = new ConsoleTable();
    foreach ($params['arguments'] as $arg => $arg_properties) {
      $aliases = '<' . $arg_properties['argument'] . '>';
      $arg_table->addRow([
        $aliases,
        $arg_properties['name'],
      ]);
    }
    $arg_table->setPadding(2);
    $arg_table->hideBorder();
    $arg_table->display();

    echo PHP_EOL;
    echo 'Flags: ' . PHP_EOL;
    $flg_table = new ConsoleTable();
    foreach ($params['flags'] as $flg => $flg_properties) {
      $aliases = '-' . $flg_properties['key'] . ', --' . $flg . ' <' . $flg_properties['argument'] . '>';
      $flg_table->addRow([
        $aliases,
        $flg_properties['name'],
      ]);
    }
    $flg_table->setPadding(2);
    $flg_table->hideBorder();
    $flg_table->display();
  }

  exit(0);
}

/**
 * Show script version.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_version($args) {
  global $preferences;
  $project_info = file_get_contents($preferences['project_info_file']);
  $project_info = json_decode($project_info, true);
  echo $project_info['version'] . PHP_EOL;
  exit(0);
}

/**
 * Show info about dependencies.
 *
 * @param $args
 *  array of arguments.
 *
 * @return
 *  Nothing.
 */
function cmd_info($args) {
  global $preferences;
  $info = [];
  $project_info = file_get_contents($preferences['project_info_file']);
  $project_info = json_decode($project_info, true);
  $info[] = 'SSHFS Mount Tool v' . $project_info['version'];
  exec('sshfs --version 2> /dev/null', $info);
  $info[] = 'PHP v' . PHP_VERSION;

  // @todo check for other dependencies
  // @todo show as table "dependency version : status"
  
  foreach ($info as $key => $line) {
    echo $line . PHP_EOL;
  }

  exit(0);
}

/**
 * Check for global option provided.
 * Set global preference if option provided.
 *
 * @param $argv
 *  Array of command line arguments.
 *
 * @return
 *  Nothing.
 */
function is_global($argv) {
  global $preferences;
  foreach ($argv as $arg_key => $arg_value) {
    if ($arg_value == '--global' || $arg_value == '-g') {
      $preferences['global'] = TRUE;
    }
  }
  return;
}

/**
 * Resolve commands and options from command line arguments.
 * Provide logic for handling different amount of provided arguments,
 * different contents and order.
 * Execute corresponding command function.
 *
 * @param $argv
 *  Array of command line arguments.
 *
 * @param $argc
 *  Amount of command line arguments.
 *
 * @return
 *  Result of command function.
 */
function resolve_args($argv, $argc) {
  global $commands;
  global $params;
  $args = [];

  if ($argc == 1) {
    // no args
    $cmd_cmd = $commands['mount']['cmd'];
  }
  else {
    // has args

    // remove first arg - script name
    array_shift($argv);  

    // check new first arg is cmd
    $cmd = match_cmd($argv[0]);
    if ($cmd) {
      $cmd_cmd = $commands[$cmd]['cmd'];
      // command found, remove it from args
      array_shift($argv);
    }
    else {
      $cmd_cmd = $commands['mount']['cmd'];
    }

    if (!empty($argv)) {
      // here left only options

      $skip_next_arg = FALSE;
      foreach ($argv as $arg_key => $arg_value) {
        // skip iteration if argument already used (for flag value)
        if (!$skip_next_arg) {
          if (substr($arg_value, 0, 1) != '-') {
            // looks like an argument

            $arg_found = FALSE;
            // check for arguments
            foreach ($params['arguments'] as $parg_key => $parg_value) {
              // check for validation
              if (isset($parg_value['validate'])) {
                $arg_validate = $parg_value['validate'];
                $arg_valid = $arg_validate($arg_value);
                if ($arg_valid) {
                  // check arg already exist
                  if (!isset($args[$parg_key])) {
                    $args[$parg_key] = $arg_valid;
                    $arg_found = TRUE;
                    break;
                  }
                }
              }
            }

            if (!$arg_found) {
              // arg not mach any validations
              echo 'Unknown command or argument ' . $arg_value . PHP_EOL;
              exit(1);
            }

          }
          else {
            // looks like an option

            $arg_found = FALSE;
            // check for options
            foreach ($params['options'] as $popt_key => $popt_value) {
              // check for long or single short option
              if ($arg_value == '--' . $popt_key || isset($popt_value['key']) && $arg_value == '-' . $popt_value['key']) {
                // check arg already exist
                if (!isset($args[$popt_key])) {
                  $args[$popt_key] = TRUE;
                  $arg_found = TRUE;
                  break;
                }
              }

              // @todo check for multiple short options
              // possible options (-vsyg)
            }

            // check for flags
            foreach ($params['flags'] as $pflg_key => $pflg_value) {
              // check for long or short flag
              if ($arg_value == '--' . $pflg_key || $arg_value == '-' . $pflg_value['key']) {
                // check arg already exist
                if (!isset($args[$pflg_key])) {
                  if (isset($argv[$arg_key + 1])) {
                    $args[$pflg_key] = $argv[$arg_key + 1];
                    $skip_next_arg = TRUE;
                    $arg_found = TRUE;
                    break;
                  }
                }
              }

              // @todo check for value goes right after key without space
              // possible flag (-psomepass)

              // @todo check for value goes right after key with equal symbol
              // possible flag (-p=somepass)
            }

            if (!$arg_found) {
              // arg not mach any options
              echo 'Unknown option ' . $arg_value . PHP_EOL;
              exit(1);
            }
            
          }
        }
        else {
          // reset skip
          $skip_next_arg = FALSE;
        }
      }
    }
  }

  if (isset($args['help']) && !isset($args['cmd'])) {
    $args['cmd'] = 'help';
    unset($args['help']);
  }

  // fallback for wrong command order
  if ($cmd_cmd == $commands['mount']['cmd'] && isset($args['cmd'])) {
    $cmd = $args['cmd'];
    $cmd_cmd = $commands[$cmd]['cmd'];
    unset($args['cmd']);
  }

  // fallback for wrong command order for help
  if ($cmd_cmd != $commands['help']['cmd'] && isset($args['cmd']) && $args['cmd'] == 'help' ||
      $cmd_cmd != $commands['help']['cmd'] && isset($args['help'])) {
    foreach ($commands as $cmd => $command_settings) {
      if ($cmd_cmd == $command_settings['cmd']) {
        $args['cmd'] = $cmd;
        $cmd_cmd = $commands['help']['cmd'];
        break;
      }
    }
  }



  // check for double command
  if (isset($args['cmd']) && $cmd_cmd == $commands[$args['cmd']]['cmd']) {
    // exception for help command
    if ($args['cmd'] != 'help') {
      echo 'Unexpected argument for ' . $args['cmd'] . PHP_EOL;
      exit(1);
    }
  }

  return $cmd_cmd($args);
}

/**
 * Main function.
 */
is_global($argv);
resolve_args($argv, $argc);
