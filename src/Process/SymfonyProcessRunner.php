<?php

declare(strict_types=1);

namespace SSHFSMountTool\Process;

use Symfony\Component\Process\Process;

final class SymfonyProcessRunner implements ProcessRunner {

  public function run(array $argv, array $env = [], ?string $input = NULL): ProcessResult {
    $process = new Process($argv, env: $env ?: NULL, input: $input);
    $process->run();
    return new ProcessResult(
      exitCode: $process->getExitCode() ?? 1,
      stdout: $process->getOutput(),
      stderr: $process->getErrorOutput(),
    );
  }

  public function runShell(string $command): ProcessResult {
    $process = Process::fromShellCommandline($command);
    $process->run();
    return new ProcessResult(
      exitCode: $process->getExitCode() ?? 1,
      stdout: $process->getOutput(),
      stderr: $process->getErrorOutput(),
    );
  }

  public function runTty(array $argv, array $env = []): int {
    $process = new Process($argv, env: $env ?: NULL);
    $process->setTty(TRUE);
    $process->setTimeout(NULL);
    $process->run();
    return $process->getExitCode() ?? 1;
  }

}
