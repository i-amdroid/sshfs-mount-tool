<?php

$preferences['mount_cmd'] = 'sshfs';
$preferences['unmount_cmd'] = 'umount';
$preferences['mounts_list_cmd'] = 'mount';
$preferences['default_options'] = array(
  'follow_symlinks',
  'allow_other',
  'defer_permissions',
);

function gen_mount_cmd($cid, $password = FALSE) {
  global $preferences;
  $connection_settings = get_connection_settings($cid);
  // use provided password
  if ($password) {
    $connection_settings['password'] = $password;
  }
  $options = [];
  $cmd = [];

  $options[] = 'volname=' . $connection_settings['title'];
  $options = array_merge($options, $preferences['default_options']);
  // if ($connection_settings['options']) {
  if (isset($connection_settings['options'])) {
    $options = array_merge($options, $connection_settings['options']);
  }
  if ($connection_settings['password']) {
    $cmd[] = 'echo ' . $connection_settings['password'] . ' |';
    $options[] = 'password_stdin';
  }
  if ($connection_settings['key']) {
    $options[] = 'IdentityFile=' . $connection_settings['key'];
  }
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
  global $preferences;
  $connection_settings = get_connection_settings($cid);
  return $preferences['unmount_cmd'] . ' ' . $connection_settings['mount'];
}

function get_mounts() {
  global $preferences;
  $mount_output = [];
  $mounts = [];
  $cids = [];
  exec($preferences['mounts_list_cmd'] . ' | grep -i osxfuse', $mount_output);
  foreach ($mount_output as $key => $value) {
    $values = explode(' ', $value);
    if ($values[2]) {
      $mounts[] = $values[2];
    }
  }
  foreach ($mounts as $key => $mount_point) {
    $cid = get_cid($mount_point);
    if ($cid) {
      $cids[] = $cid;
    }
  }
  return $cids;
}

function run_terminal_cmd($cmd, $new_tab = TRUE) {
  if ($new_tab) {
    return shell_exec('osascript -e \'tell application "Terminal" to activate\' -e \'tell application "System Events" to tell process "Terminal" to keystroke "t" using command down\' -e \'tell application "Terminal" to do script "' . $cmd . '" in selected tab of the front window\'');
  } else {
    return shell_exec('osascript -e \'tell application "Terminal" to do script "' . $cmd . '"\'');
  }
}
