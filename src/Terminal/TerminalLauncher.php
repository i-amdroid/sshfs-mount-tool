<?php

declare(strict_types=1);

namespace SSHFSMountTool\Terminal;

use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Process\ProcessRunner;

/**
 * Wraps a command with the active terminal emulator's prefix/suffix and runs it.
 *
 * The terminal prefix/suffix is inherently shell-syntax (AppleScript strings,
 * bash -c, etc.) so this is the one place that legitimately uses the shell.
 * The inner command must be provided already shell-safe by the caller.
 */
final class TerminalLauncher {

  public function __construct(
    private readonly Preferences $preferences,
    private readonly ProcessRunner $runner,
  ) {}

  public function launch(string $command): ProcessResult {
    $terminal = $this->preferences->terminal();
    if ($terminal === NULL) {
      throw new \RuntimeException(sprintf(
        'Terminal "%s" is not configured',
        $this->preferences->terminalName,
      ));
    }

    return $this->runner->runShell($terminal->wrap($command));
  }

}
