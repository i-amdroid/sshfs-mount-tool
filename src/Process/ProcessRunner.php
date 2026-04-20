<?php

declare(strict_types=1);

namespace SSHFSMountTool\Process;

/**
 * Thin abstraction over Symfony Process so mount/unmount/ssh execution
 * can be faked in tests without touching the real system.
 */
interface ProcessRunner {

  /**
   * Run a command given as an argv list (preferred — no shell).
   *
   * @param list<string>            $argv
   * @param array<string, string>   $env   Extra env vars
   * @param string|null             $input Stdin payload (e.g. password for sshfs)
   */
  public function run(array $argv, array $env = [], ?string $input = NULL): ProcessResult;

  /**
   * Run a command string through a shell. Use sparingly — only needed when
   * the command itself is a shell-quoted string (e.g. a terminal wrapper).
   */
  public function runShell(string $command): ProcessResult;

  /**
   * Run interactively attached to the user's TTY (e.g. $EDITOR, ssh).
   *
   * @param list<string>          $argv
   * @param array<string, string> $env  Extra env vars
   */
  public function runTty(array $argv, array $env = []): int;

}
