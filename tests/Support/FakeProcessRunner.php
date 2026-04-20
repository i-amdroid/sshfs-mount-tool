<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Support;

use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Process\ProcessRunner;

final class FakeProcessRunner implements ProcessRunner {

  /** @var list<ProcessCall> */
  public array $calls = [];

  /** @var list<ProcessResult> */
  private array $runResults = [];

  /** @var list<ProcessResult> */
  private array $shellResults = [];

  private int $runCursor = 0;

  private int $shellCursor = 0;

  public int $ttyExit = 0;

  public function queueRun(ProcessResult $result): void {
    $this->runResults[] = $result;
  }

  public function queueShell(ProcessResult $result): void {
    $this->shellResults[] = $result;
  }

  public function run(array $argv, array $env = [], ?string $input = NULL): ProcessResult {
    $this->calls[] = new ProcessCall(type: 'run', argv: $argv, env: $env, input: $input);
    return $this->runResults[$this->runCursor++] ?? new ProcessResult(0, '', '');
  }

  public function runShell(string $command): ProcessResult {
    $this->calls[] = new ProcessCall(type: 'shell', command: $command);
    return $this->shellResults[$this->shellCursor++] ?? new ProcessResult(0, '', '');
  }

  public function runTty(array $argv, array $env = []): int {
    $this->calls[] = new ProcessCall(type: 'tty', argv: $argv, env: $env);
    return $this->ttyExit;
  }

}
