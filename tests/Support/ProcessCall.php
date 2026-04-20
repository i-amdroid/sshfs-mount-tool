<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Support;

final class ProcessCall {

  /**
   * @param list<string>          $argv
   * @param array<string, string> $env
   */
  public function __construct(
    public readonly string $type,
    public readonly array $argv = [],
    public readonly array $env = [],
    public readonly ?string $input = NULL,
    public readonly string $command = '',
  ) {}

}
