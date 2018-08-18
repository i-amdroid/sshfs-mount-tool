<?php

$default_preferences['unmount_cmd'] = 'fusermount -u';
$default_preferences['mounts_list_type'] = 'fuse.sshfs';
// $default_preferences['editor'] = 'editor';
$default_preferences['editor'] = '$EDITOR';
$default_preferences['terminal'] = 'gnome-terminal';

$default_terminals['gnome-terminal'] = [
  'cmd_prefix' => 'gnome-terminal --tab -- bash -c "',
  'cmd_suffix' => '; exec bash"',
];
