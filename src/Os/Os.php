<?php

declare(strict_types=1);

namespace SSHFSMountTool\Os;

enum Os: string {

  case Darwin = 'Darwin';
  case Linux = 'Linux';

  public static function detect(): self {
    return match (PHP_OS_FAMILY) {
      'Darwin' => self::Darwin,
      'Linux' => self::Linux,
      default => throw new \RuntimeException('Unsupported operating system: ' . PHP_OS_FAMILY),
    };
  }

}
