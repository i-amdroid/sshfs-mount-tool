<?php

declare(strict_types=1);

namespace SSHFSMountTool\Ssh;

use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Connection\Connection;

/**
 * Builds the core `ssh …` invocation for a connection as an argv list.
 *
 * The session launcher may add `sshpass -f <file>` in front when a password
 * is configured; that stays out of this builder to keep it pure.
 */
final class SshCommandBuilder {

  public function __construct(
    private readonly Preferences $preferences,
  ) {}

  /**
   * @return list<string>
   */
  public function buildArgv(Connection $connection): array {
    $argv = ['ssh'];

    foreach ([...$this->preferences->defaultSshOptions, ...$connection->sshOptions] as $option) {
      foreach (self::splitOption($option) as $token) {
        $argv[] = $token;
      }
    }

    $argv[] = $connection->user !== NULL
      ? $connection->user . '@' . $connection->server
      : $connection->server;

    if ($connection->port !== NULL) {
      $argv[] = '-p';
      $argv[] = (string) $connection->port;
    }

    return $argv;
  }

  /**
   * Render an argv list as a shell command string suitable for a terminal
   * emulator's `do script` or `bash -c` wrapper. Every argument is quoted.
   *
   * @param list<string> $argv
   */
  public static function argvToShell(array $argv): string {
    return implode(' ', array_map(escapeshellarg(...), $argv));
  }

  /**
   * Configuration stores ssh options as free-form strings like
   * `"-o ServerAliveInterval=60"`. Split into argv tokens for argv-form
   * execution. Naive whitespace split is adequate for the ssh option
   * surface we care about.
   *
   * @return list<string>
   */
  private static function splitOption(string $option): array {
    $option = trim($option);
    if ($option === '') {
      return [];
    }
    $parts = preg_split('/\s+/', $option);
    return $parts === FALSE ? [$option] : array_values($parts);
  }

}
