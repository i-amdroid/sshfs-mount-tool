<?php

declare(strict_types=1);

namespace SSHFSMountTool\Config;

use SSHFSMountTool\Connection\Connection;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML-backed store for saved connections.
 *
 * Reads from the active config file resolved by {@see Preferences::activeConfigFile()};
 * writes may be directed to the user-level file or the per-project file.
 */
final class ConnectionRepository {

  private const int FILE_MODE = 0o600;
  private const int DIR_MODE = 0o700;

  public function __construct(
    private readonly Preferences $preferences,
  ) {}

  /**
   * @return array<string, Connection>
   */
  public function all(): array {
    $data = $this->readFile($this->preferences->activeConfigFile());
    $raw = self::connectionsFrom($data);

    $out = [];
    foreach ($raw as $id => $settings) {
      if (!is_array($settings)) {
        continue;
      }
      /** @var array<string, mixed> $settings */
      $out[$id] = Connection::fromArray($id, $settings);
    }
    return $out;
  }

  public function find(string $id): ?Connection {
    return $this->all()[$id] ?? NULL;
  }

  public function exists(string $id): bool {
    return array_key_exists($id, $this->all());
  }

  /**
   * Save (insert or overwrite) a connection.
   *
   * @param bool $useCurrent  When true, write to ./smt.yml; otherwise user file.
   */
  public function save(Connection $connection, bool $useCurrent = FALSE): void {
    $file = $useCurrent
      ? $this->preferences->currentConfigFile
      : $this->preferences->userConfigFile;

    $data = $this->readFile($file);
    $connections = self::connectionsFrom($data);
    $connections[$connection->id] = $connection->toArray();
    $data['connections'] = $connections;

    $this->writeFile($file, $data);
  }

  public function remove(string $id): void {
    $file = $this->preferences->activeConfigFile();
    $data = $this->readFile($file);
    $connections = self::connectionsFrom($data);
    if (!array_key_exists($id, $connections)) {
      return;
    }
    unset($connections[$id]);
    $data['connections'] = $connections;
    $this->writeFile($file, $data);
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  private static function connectionsFrom(array $data): array {
    $value = $data['connections'] ?? [];
    if (!is_array($value)) {
      return [];
    }
    /** @var array<string, mixed> $value */
    return $value;
  }

  /**
   * @return array<string, mixed>
   */
  private function readFile(string $file): array {
    if (!file_exists($file)) {
      return [];
    }
    $parsed = Yaml::parseFile($file);
    if (!is_array($parsed)) {
      return [];
    }
    /** @var array<string, mixed> $parsed */
    return $parsed;
  }

  /**
   * @param array<string, mixed> $data
   */
  private function writeFile(string $file, array $data): void {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, self::DIR_MODE, TRUE) && !is_dir($dir)) {
      throw new \RuntimeException('Cannot create config directory: ' . $dir);
    }
    $yaml = Yaml::dump($data, 4, 2);
    if (file_put_contents($file, $yaml) === FALSE) {
      throw new \RuntimeException('Cannot write config file: ' . $file);
    }
    @chmod($file, self::FILE_MODE);
  }

}
