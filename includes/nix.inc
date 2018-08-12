<?php

$preferences['unmount_cmd'] = 'fusermount -u';
$preferences['mounts_list_type'] = 'fuse.sshfs';
$preferences['editor'] = 'editor';
$preferences['terminal'] = 'gnome-terminal';

$terminals['gnome-terminal'] = [
  'cmd_prefix' => 'gnome-terminal --tab -- bash -c "',
  'cmd_suffix' => '; exec bash"',
];
