<?php

$default_preferences['unmount_cmd'] = 'umount';
$default_preferences['mounts_list_type'] = 'osxfuse';
$default_preferences['editor'] = '$EDITOR';
$default_preferences['terminal'] = 'Terminal.app';

$default_terminals['Terminal.app'] = [
  'cmd_prefix' => 'osascript -e \'tell application "Terminal" to activate\' -e \'tell application "System Events" to tell process "Terminal" to keystroke "t" using command down\' -e \'tell application "Terminal" to do script "',
  'cmd_suffix' => '" in selected tab of the front window\'',
];
$default_terminals['iTerm.app'] = [
  'cmd_prefix' => 'osascript -e \'tell application "iTerm2" to tell current window to set newWindow to (create tab with default profile)\' -e \'tell application "iTerm2" to tell current session of newWindow to write text "',
  'cmd_suffix' => '"\'',
];
