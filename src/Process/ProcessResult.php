<?php

declare(strict_types=1);

namespace SSHFSMountTool\Process;

final class ProcessResult {

  public function __construct(
    public readonly int $exitCode,
    public readonly string $stdout,
    public readonly string $stderr,
  ) {}

  public function isSuccessful(): bool {
    return $this->exitCode === 0;
  }

}
