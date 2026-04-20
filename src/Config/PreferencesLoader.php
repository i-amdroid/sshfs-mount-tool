<?php

declare(strict_types=1);

namespace SSHFSMountTool\Config;

use SSHFSMountTool\Os\Os;
use SSHFSMountTool\Os\OsDefaults;
use SSHFSMountTool\Terminal\Terminal;
use Symfony\Component\Yaml\Yaml;

final class PreferencesLoader {

  /**
   * User config is written with secure 0600 perms (it may contain passwords).
   */
  private const int FILE_MODE = 0o600;
  private const int DIR_MODE = 0o700;

  public function __construct(
    private readonly ?string $homePath = NULL,
    private readonly ?string $currentPath = NULL,
  ) {}

  public function load(): Preferences {
    $os = Os::detect();
    $home = $this->homePath ?? (getenv('HOME') ?: '');
    $current = $this->currentPath ?? (getcwd() ?: '');

    if ($home === '') {
      throw new \RuntimeException('Cannot determine user home directory.');
    }

    $user_preferences_file = $home . '/.config/smt/config.yml';
    $os_defaults = OsDefaults::preferences($os);
    $os_terminals = OsDefaults::terminals($os);

    $defaults = [
      'mount_path' => '~/mnt',
      'mount_cmd' => 'sshfs',
      'mounts_list_cmd' => 'mount',
      'mounts_list_type' => $os_defaults['mounts_list_type'],
      'unmount_cmd' => $os_defaults['unmount_cmd'],
      'editor' => $os_defaults['editor'],
      'terminal' => $os_defaults['terminal'],
      'default_options' => [
        'follow_symlinks',
        // accept-new: trust keys on first connect, still reject on change.
        'StrictHostKeyChecking=accept-new',
        // Auto-reconnect on transient network drops.
        'reconnect',
        // Detect a dead server within ~45s instead of hanging the mount.
        'ServerAliveInterval=15',
        'ServerAliveCountMax=3',
      ],
      'default_ssh_options' => [
        '-o ServerAliveInterval=60',
        '-o ServerAliveCountMax=3',
      ],
      'global' => FALSE,
    ];

    if (!file_exists($user_preferences_file)) {
      $this->writeBareUserConfig($user_preferences_file, $defaults);
    }

    $user = $this->readUserConfig($user_preferences_file);
    $user_preferences_raw = $user['preferences'] ?? [];
    $user_terminals_raw = $user['terminals'] ?? [];
    $user_preferences = is_array($user_preferences_raw) ? $user_preferences_raw : [];
    $user_terminals = is_array($user_terminals_raw) ? $user_terminals_raw : [];

    /** @var array<string, mixed> $merged */
    $merged = array_merge($defaults, $user_preferences);

    $terminals = $os_terminals;
    foreach ($user_terminals as $name => $def) {
      if (!is_array($def)) {
        continue;
      }
      $terminals[(string) $name] = new Terminal(
        cmdPrefix: self::asString($def['cmd_prefix'] ?? ''),
        cmdSuffix: self::asString($def['cmd_suffix'] ?? ''),
      );
    }

    $unmount_cmd = $merged['unmount_cmd'];
    if (is_string($unmount_cmd)) {
      $split = preg_split('/\s+/', trim($unmount_cmd));
      $unmount_cmd = $split === FALSE ? [] : $split;
    }
    if (!is_array($unmount_cmd)) {
      $unmount_cmd = [];
    }

    return new Preferences(
      os: $os,
      homePath: $home,
      currentPath: $current,
      userConfigFile: $home . '/.config/smt/smt.yml',
      currentConfigFile: $current . '/smt.yml',
      userPreferencesFile: $user_preferences_file,
      mountPath: self::asString($merged['mount_path']),
      mountCmd: self::asString($merged['mount_cmd']),
      mountsListCmd: self::asString($merged['mounts_list_cmd']),
      mountsListType: self::asString($merged['mounts_list_type']),
      unmountCmd: self::asStringList($unmount_cmd),
      editor: self::asString($merged['editor']),
      terminalName: self::asString($merged['terminal']),
      terminals: $terminals,
      defaultOptions: self::asStringList($merged['default_options']),
      defaultSshOptions: self::asStringList($merged['default_ssh_options']),
      global: $merged['global'] === TRUE,
    );
  }

  /**
   * Cast a config value to string, falling back to an empty string for
   * non-scalar input so the analyzer can prove the result is `string`.
   */
  private static function asString(mixed $value): string {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * @return list<string>
   */
  private static function asStringList(mixed $value): array {
    if (!is_array($value)) {
      return [];
    }
    $out = [];
    foreach ($value as $item) {
      if (!is_scalar($item)) {
        continue;
      }

      $out[] = (string) $item;
    }
    return $out;
  }

  /**
   * @param array<string, mixed> $defaults
   */
  private function writeBareUserConfig(string $file, array $defaults): void {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, self::DIR_MODE, TRUE) && !is_dir($dir)) {
      throw new \RuntimeException('Cannot create config directory: ' . $dir);
    }

    // Dump defaults as YAML and comment every line so users can uncomment
    // and tweak. Blank preferences/terminals sections are appended.
    $dump = Yaml::dump(['default_preferences' => $defaults, 'default_terminals' => []], 4, 2);
    $commented =
      implode(PHP_EOL, array_map(
        static fn (string $line): string => $line === '' ? '' : '# ' . $line,
        explode(PHP_EOL, rtrim($dump, PHP_EOL)),
      )) . PHP_EOL;

    $commented .= PHP_EOL . 'preferences:' . PHP_EOL . PHP_EOL . 'terminals:' . PHP_EOL;

    file_put_contents($file, $commented);
    @chmod($file, self::FILE_MODE);
  }

  /**
   * @return array<string, mixed>
   */
  private function readUserConfig(string $file): array {
    if (!file_exists($file)) {
      return [];
    }
    $parsed = Yaml::parseFile($file);
    if (!is_array($parsed)) {
      return [];
    }
    /** @var array<string, mixed> $parsed */
    return $parsed;
  }

}
