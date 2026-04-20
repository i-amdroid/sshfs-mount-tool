<?php

declare(strict_types=1);

namespace SSHFSMountTool\Mount;

use SSHFSMountTool\Config\ConnectionRepository;
use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Process\ProcessRunner;
use SSHFSMountTool\Util\PathExpander;

/**
 * Lists currently-mounted connection IDs by reading `mount` output.
 *
 * Avoids piping through grep; filtering is done in PHP against
 * {@see Preferences::$mountsListType} (e.g. `macfuse`, `fuse.sshfs`).
 */
final class MountInspector {

  /**
   * Linux format: `<src> on <target> type <fstype> (<opts>)`.
   * macOS format: `<src> on <target> (<fstype>, <opts>)`.
   *
   * Both support paths with spaces, so we anchor the greedy `.+?` on the
   * distinct `type <fstype>` or ` (<fstype>` trailer.
   */
  private const string MOUNT_LINE_REGEX = '/\son\s(?<target>.+?)\s(?:type\s(?<lt>\S+)|\((?<mt>[^,)]+))/';

  public function __construct(
    private readonly Preferences $preferences,
    private readonly ConnectionRepository $connections,
    private readonly ProcessRunner $runner,
    private readonly PathExpander $pathExpander,
  ) {}

  /**
   * @return list<string>  Connection IDs currently mounted.
   */
  public function mountedIds(): array {
    $result = $this->runner->run([$this->preferences->mountsListCmd]);
    if (!$result->isSuccessful()) {
      return [];
    }

    $type = strtolower($this->preferences->mountsListType);
    $mount_points = [];
    $lines = preg_split('/\r?\n/', $result->stdout);
    foreach ($lines === FALSE ? [] : $lines as $line) {
      if ($line === '' || stripos($line, $type) === FALSE) {
        continue;
      }
      $matches = [];
      if (preg_match(self::MOUNT_LINE_REGEX, $line, $matches) !== 1) {
        continue;
      }
      $fstype = strtolower($matches['lt'] !== '' ? $matches['lt'] : $matches['mt']);
      if (!str_contains($fstype, $type)) {
        continue;
      }
      $mount_points[] = $this->decodeMountPath($matches['target']);
    }

    $ids = [];
    foreach ($mount_points as $mount_point) {
      $id = $this->matchConnectionByMountPoint($mount_point);
      if ($id !== NULL) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

  /**
   * Linux `mount` octal-escapes spaces/tabs/newlines/backslashes in paths
   * (e.g. `/mnt/My\040Server`). Decode the common cases so we can match
   * paths back to configured connections.
   */
  private function decodeMountPath(string $raw): string {
    return (string) preg_replace_callback(
      '/\\\\([0-7]{3})/',
      static fn (array $m): string => chr((int) octdec($m[1])),
      $raw,
    );
  }

  private function matchConnectionByMountPoint(string $mountPoint): ?string {
    foreach ($this->connections->all() as $id => $connection) {
      if ($connection->mount === NULL) {
        continue;
      }
      if ($mountPoint === $connection->mount) {
        return $id;
      }
      if ($mountPoint === $this->pathExpander->expand($connection->mount)) {
        return $id;
      }
    }
    return NULL;
  }

}
