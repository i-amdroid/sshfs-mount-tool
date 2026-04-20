<?php

declare(strict_types=1);

namespace SSHFSMountTool\Connection;

/**
 * Immutable connection settings as loaded from the YAML config file.
 */
final class Connection {

  /**
   * @param list<string> $options     SSHFS -o options
   * @param list<string> $sshOptions  Extra flags passed to ssh
   */
  public function __construct(
    public readonly string $id,
    public readonly string $title,
    public readonly string $server,
    public readonly ?int $port = NULL,
    public readonly ?string $user = NULL,
    #[\SensitiveParameter]
    public readonly ?string $password = NULL,
    public readonly ?string $key = NULL,
    public readonly ?string $mount = NULL,
    public readonly ?string $remote = NULL,
    public readonly array $options = [],
    public readonly array $sshOptions = [],
  ) {}

  /**
   * @param array<string, mixed> $raw  Raw YAML payload
   */
  public static function fromArray(string $id, array $raw): self {
    $port = $raw['port'] ?? NULL;
    return new self(
      id: $id,
      title: (string) ($raw['title'] ?? $id),
      server: (string) ($raw['server'] ?? ''),
      port: $port === NULL || $port === '' ? NULL : (int) $port,
      user: self::optionalString($raw['user'] ?? NULL),
      password: self::optionalString($raw['password'] ?? NULL),
      key: self::optionalString($raw['key'] ?? NULL),
      mount: self::optionalString($raw['mount'] ?? NULL),
      remote: self::optionalString($raw['remote'] ?? NULL),
      options: self::normalizeList($raw['options'] ?? []),
      sshOptions: self::normalizeList($raw['ssh_options'] ?? []),
    );
  }

  private static function optionalString(mixed $value): ?string {
    if ($value === NULL) {
      return NULL;
    }
    $value = (string) $value;
    return $value === '' ? NULL : $value;
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'title' => $this->title,
      'server' => $this->server,
      'port' => $this->port,
      'user' => $this->user,
      'password' => $this->password,
      'key' => $this->key,
      'mount' => $this->mount,
      'remote' => $this->remote,
      'options' => $this->options,
      'ssh_options' => $this->sshOptions,
    ];
  }

  /**
   * @param mixed $value
   * @return list<string>
   */
  private static function normalizeList(mixed $value): array {
    if (!is_array($value)) {
      return [];
    }
    $out = [];
    foreach ($value as $item) {
      $item = trim((string) $item);
      if ($item !== '') {
        $out[] = $item;
      }
    }
    return $out;
  }

}
