<?php

declare(strict_types=1);

namespace SSHFSMountTool\Ssh;

use SSHFSMountTool\Connection\Connection;
use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Process\ProcessRunner;
use SSHFSMountTool\Terminal\TerminalLauncher;

/**
 * Runs an ssh session either attached to the current terminal tab
 * (default, `sameTab`) or in a newly-spawned tab (`newTab`).
 *
 * When a password is set, it is written to a 0600 tempfile and consumed
 * via `sshpass -f`. The password never appears on the command line.
 */
final class SshSessionLauncher {

  private const int PASSWORD_FILE_MODE = 0o600;

  public function __construct(
    private readonly SshCommandBuilder $builder,
    private readonly ProcessRunner $runner,
    private readonly TerminalLauncher $terminal,
  ) {}

  /**
   * Run ssh attached to the current terminal (stdin/stdout/stderr).
   * Blocks until ssh exits; returns ssh's exit code.
   */
  public function launchInCurrentTab(
    Connection $connection,
    #[\SensitiveParameter]
    ?string $passwordOverride = NULL,
  ): int {
    $argv = $this->builder->buildArgv($connection);
    $password = $passwordOverride ?? $connection->password;

    if ($password === NULL || $password === '') {
      return $this->runner->runTty($argv);
    }

    $file = $this->writePasswordFile($password);
    try {
      return $this->runner->runTty(['sshpass', '-f', $file, ...$argv]);
    }
    finally {
      @unlink($file);
    }
  }

  /**
   * Spawn a new terminal tab and run ssh there. The smt process returns
   * as soon as the tab is spawned — it does not follow ssh's lifecycle.
   */
  public function launchInNewTab(
    Connection $connection,
    #[\SensitiveParameter]
    ?string $passwordOverride = NULL,
  ): ProcessResult {
    $argv = $this->builder->buildArgv($connection);
    $ssh_command = SshCommandBuilder::argvToShell($argv);
    $password = $passwordOverride ?? $connection->password;

    if ($password === NULL || $password === '') {
      return $this->terminal->launch($ssh_command);
    }

    // The tempfile is self-deleted by the spawned shell after ssh exits —
    // we cannot unlink it here since the parent returns immediately.
    $file = $this->writePasswordFile($password);
    $quoted = escapeshellarg($file);
    return $this->terminal->launch(
      sprintf('sshpass -f %s %s; rm -f %s', $quoted, $ssh_command, $quoted),
    );
  }

  private function writePasswordFile(#[\SensitiveParameter] string $password): string {
    $file = tempnam(sys_get_temp_dir(), 'smt-sshpass-');
    if ($file === FALSE) {
      throw new \RuntimeException('Cannot create temporary password file');
    }
    @chmod($file, self::PASSWORD_FILE_MODE);
    if (file_put_contents($file, $password) === FALSE) {
      throw new \RuntimeException('Cannot write temporary password file');
    }
    return $file;
  }

}
