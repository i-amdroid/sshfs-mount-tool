<?php

declare(strict_types=1);

namespace SSHFSMountTool\Mount;

/**
 * A fully-resolved mount invocation: argv vector plus optional stdin payload.
 */
final class MountCommand {

  /**
   * @param list<string> $argv
   */
  public function __construct(
    public readonly array $argv,
    public readonly ?string $stdin = NULL,
  ) {}

}
