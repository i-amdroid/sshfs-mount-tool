<?php

$preferences['mount_cmd'] = 'sshfs';
$preferences['unmount_cmd'] = 'fusermount -u';
$preferences['mounts_list_cmd'] = 'mount';
$preferences['editor'] = 'editor';
$preferences['default_options'] = array(
  'follow_symlinks',
  // 'allow_other',
  // 'defer_permissions',
  'StrictHostKeyChecking=no',
);

function gen_mount_cmd($cid, $password = FALSE, $masked = FALSE) {
  global $preferences;
  $connection_settings = get_connection_settings($cid);
  // use provided password
  if ($password) {
    $connection_settings['password'] = $password;
  }
  $options = [];
  $cmd = [];

  // $options[] = 'volname=' . $connection_settings['title'];
  $options = array_merge($options, $preferences['default_options']);
  if (isset($connection_settings['options'])) {
    $options = array_merge($options, $connection_settings['options']);
  }
  if ($connection_settings['password']) {
    if ($masked) {
      $cmd[] = 'echo [password] |';
    }
    else {
      $cmd[] = 'echo ' . $connection_settings['password'] . ' |';
    }
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

  // mountpoint requred for mounting
  if (isset($connection_settings['mount'])) {
    $cmd[] = $connection_settings['mount'];
  }
  else {
    echo 'No mountpoint for ' . $cid .  ' set' . PHP_EOL;
    exit(1);
  }

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
  exec($preferences['mounts_list_cmd'] . ' | grep -i fuse.sshfs', $mount_output);
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
    return shell_exec('gnome-terminal --tab -- bash -c "' . $cmd . '; exec bash"');
  } else {
    return shell_exec('gnome-terminal -- bash -c "' . $cmd . '; exec bash"');
  }
}
