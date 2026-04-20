<?php

declare(strict_types=1);

namespace SSHFSMountTool\Mount;

use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Connection\Connection;
use SSHFSMountTool\Os\Os;
use SSHFSMountTool\Util\PathExpander;

/**
 * Builds argv for `sshfs` (and `umount`) without touching a shell.
 *
 * The password, when present, is passed via stdin (`password_stdin`) rather
 * than embedded in the command line — protects it from `ps` and from any
 * shell interpretation.
 *
 * `~`-prefixed paths are expanded here: argv form bypasses the shell, so
 * sshfs/umount would otherwise treat `~` as a literal directory name.
 */
final class MountCommandBuilder {

  public function __construct(
    private readonly Preferences $preferences,
    private readonly PathExpander $pathExpander,
  ) {}

  public function buildMount(Connection $connection, ?string $passwordOverride = NULL): MountCommand {
    if ($connection->mount === NULL) {
      throw new \RuntimeException('No mount point set for connection ' . $connection->id);
    }

    $password = $passwordOverride ?? $connection->password;

    $options = [];
    if ($this->preferences->os === Os::Darwin) {
      $options[] = 'volname=' . $connection->id;
    }
    array_push($options, ...$this->preferences->defaultOptions);
    array_push($options, ...$connection->options);
    if ($connection->key !== NULL) {
      $options[] = 'IdentityFile=' . $this->pathExpander->expand($connection->key);
    }
    if ($password !== NULL && $password !== '') {
      $options[] = 'password_stdin';
    }

    $remote = $connection->user !== NULL
      ? $connection->user . '@' . $connection->server . ':' . ($connection->remote ?? '')
      : $connection->server . ':' . ($connection->remote ?? '');

    $argv = [
      $this->preferences->mountCmd,
      $remote,
      $this->pathExpander->expand($connection->mount),
      '-o',
      implode(',', $options),
    ];
    if ($connection->port !== NULL) {
      $argv[] = '-p';
      $argv[] = (string) $connection->port;
    }

    return new MountCommand(
      argv: $argv,
      stdin: $password !== '' ? $password : NULL,
    );
  }

  /**
   * @return list<string>
   */
  public function buildUnmount(Connection $connection): array {
    if ($connection->mount === NULL) {
      throw new \RuntimeException('No mount point set for connection ' . $connection->id);
    }
    return [...$this->preferences->unmountCmd, $this->pathExpander->expand($connection->mount)];
  }

  /**
   * Render a display-safe form of the mount command for verbose output.
   * Password bytes are never rendered — they flow through stdin anyway.
   */
  public function displayMount(MountCommand $command): string {
    $parts = array_map(self::displayArg(...), $command->argv);
    $rendered = implode(' ', $parts);
    if ($command->stdin !== NULL) {
      $rendered = 'echo [password] | ' . $rendered;
    }
    return $rendered;
  }

  private static function displayArg(string $arg): string {
    if ($arg === '' || preg_match('/[^A-Za-z0-9_@:\/\.\-=,~+]/', $arg) === 1) {
      return "'" . str_replace("'", "'\\''", $arg) . "'";
    }
    return $arg;
  }

}
