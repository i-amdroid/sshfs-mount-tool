<?php

declare(strict_types=1);

namespace SSHFSMountTool\Os;

use SSHFSMountTool\Terminal\Terminal;

/**
 * Per-OS defaults for mount/unmount commands and terminal emulators.
 */
final class OsDefaults {

  /**
   * @return array{
   *   unmount_cmd: list<string>,
   *   mounts_list_type: string,
   *   editor: string,
   *   terminal: string,
   * }
   */
  public static function preferences(Os $os): array {
    return match ($os) {
      Os::Darwin => [
        'unmount_cmd' => ['umount'],
        'mounts_list_type' => 'macfuse',
        'editor' => '$EDITOR',
        'terminal' => 'Terminal.app',
      ],
      Os::Linux => [
        'unmount_cmd' => ['fusermount', '-u'],
        'mounts_list_type' => 'fuse.sshfs',
        'editor' => '$EDITOR',
        'terminal' => 'gnome-terminal',
      ],
    };
  }

  /**
   * @return array<string, Terminal>
   */
  public static function terminals(Os $os): array {
    return match ($os) {
      Os::Darwin => [
        'Terminal.app' => new Terminal(
          cmdPrefix: "osascript -e 'tell application \"Terminal\" to activate' "
          . "-e 'tell application \"System Events\" to tell process \"Terminal\" to keystroke \"t\" using command down' "
          . "-e 'tell application \"Terminal\" to do script \"",
          cmdSuffix: "\" in selected tab of the front window'",
        ),
        'iTerm.app' => new Terminal(
          cmdPrefix: "osascript -e 'tell application \"iTerm2\" to tell current window to set newWindow to (create tab with default profile)' "
          . "-e 'tell application \"iTerm2\" to tell current session of newWindow to write text \"",
          cmdSuffix: "\"'",
        ),
      ],
      Os::Linux => [
        'gnome-terminal' => new Terminal(
          cmdPrefix: 'gnome-terminal --tab -- bash -c "',
          cmdSuffix: '; exec bash"',
        ),
      ],
    };
  }

}
