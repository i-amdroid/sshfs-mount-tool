<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Support;

use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Os\Os;
use SSHFSMountTool\Terminal\Terminal;

/**
 * Builds fully-resolved {@see Preferences} instances for tests without
 * touching the filesystem or real OS detection.
 */
final class PreferencesFactory {

  /**
   * @param array{
   *   os?: Os,
   *   home?: string,
   *   current?: string,
   *   unmount?: list<string>,
   *   mounts_list_type?: string,
   *   mount_path?: string,
   *   terminal?: string,
   *   global?: bool,
   * } $overrides
   */
  public static function create(array $overrides = []): Preferences {
    $home = $overrides['home'] ?? '/home/iam';
    $current = $overrides['current'] ?? '/tmp/work';

    return new Preferences(
      os: $overrides['os'] ?? Os::Linux,
      homePath: $home,
      currentPath: $current,
      userConfigFile: $home . '/.config/smt/smt.yml',
      currentConfigFile: $current . '/smt.yml',
      userPreferencesFile: $home . '/.config/smt/config.yml',
      mountPath: $overrides['mount_path'] ?? '~/mnt',
      mountCmd: 'sshfs',
      mountsListCmd: 'mount',
      mountsListType: $overrides['mounts_list_type'] ?? 'fuse.sshfs',
      unmountCmd: $overrides['unmount'] ?? ['fusermount', '-u'],
      editor: 'vim',
      terminalName: $overrides['terminal'] ?? 'gnome-terminal',
      terminals: [
        'gnome-terminal' => new Terminal('gnome-terminal --tab -- bash -c "', '; exec bash"'),
      ],
      defaultOptions: [
        'follow_symlinks',
        'StrictHostKeyChecking=accept-new',
        'reconnect',
        'ServerAliveInterval=15',
        'ServerAliveCountMax=3',
      ],
      defaultSshOptions: [
        '-o ServerAliveInterval=60',
        '-o ServerAliveCountMax=3',
      ],
      global: $overrides['global'] ?? FALSE,
    );
  }

}
