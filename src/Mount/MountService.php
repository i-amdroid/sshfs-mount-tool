<?php

declare(strict_types=1);

namespace SSHFSMountTool\Mount;

use SSHFSMountTool\Connection\Connection;
use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Process\ProcessRunner;
use SSHFSMountTool\Util\PathExpander;

final class MountService {

  private const int MOUNT_DIR_MODE = 0o700;

  public function __construct(
    private readonly MountCommandBuilder $builder,
    private readonly ProcessRunner $runner,
    private readonly PathExpander $pathExpander,
  ) {}

  public function mount(Connection $connection, #[\SensitiveParameter] ?string $password = NULL): ProcessResult {
    $this->ensureMountDirectory($connection);
    $command = $this->builder->buildMount($connection, $password);
    return $this->runner->run($command->argv, input: $command->stdin);
  }

  public function unmount(Connection $connection): ProcessResult {
    return $this->runner->run($this->builder->buildUnmount($connection));
  }

  private function ensureMountDirectory(Connection $connection): void {
    if ($connection->mount === NULL) {
      return;
    }
    $dir = $this->pathExpander->expand($connection->mount);
    if (!is_dir($dir) && !@mkdir($dir, self::MOUNT_DIR_MODE, TRUE) && !is_dir($dir)) {
      throw new \RuntimeException('Cannot create mount directory: ' . $dir);
    }
  }

}
