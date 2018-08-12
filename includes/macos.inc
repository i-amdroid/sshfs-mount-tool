<?php

$preferences['unmount_cmd'] = 'umount';
$preferences['mounts_list_type'] = 'osxfuse';
$preferences['editor'] = '$EDITOR';
$preferences['terminal'] = 'Terminal.app';

$terminals['Terminal.app'] = [
  'cmd_prefix' => 'osascript -e \'tell application "Terminal" to activate\' -e \'tell application "System Events" to tell process "Terminal" to keystroke "t" using command down\' -e \'tell application "Terminal" to do script "',
  'cmd_suffix' => '" in selected tab of the front window\'',
];
