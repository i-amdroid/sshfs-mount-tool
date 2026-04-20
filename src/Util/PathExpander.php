<?php

declare(strict_types=1);

namespace SSHFSMountTool\Util;

final class PathExpander {

  public function __construct(
    private readonly string $homePath,
  ) {}

  /**
   * Expand a leading "~" to the configured home path.
   */
  public function expand(string $path): string {
    if (str_starts_with($path, '~')) {
      return $this->homePath . substr($path, 1);
    }
    return $path;
  }

}
