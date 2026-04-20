<?php

declare(strict_types=1);

namespace SSHFSMountTool\Terminal;

final class Terminal {

  public function __construct(
    public readonly string $cmdPrefix,
    public readonly string $cmdSuffix,
  ) {}

  public function wrap(string $command): string {
    return $this->cmdPrefix . $command . $this->cmdSuffix;
  }

}
