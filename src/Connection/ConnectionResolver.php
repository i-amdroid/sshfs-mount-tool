<?php

declare(strict_types=1);

namespace SSHFSMountTool\Connection;

use SSHFSMountTool\Config\ConnectionRepository;
use SSHFSMountTool\Mount\MountInspector;
use SSHFSMountTool\Table\ConnectionTableRenderer;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prompts the user to pick a connection ID when none is supplied via CLI.
 *
 * @phpstan-type ConnectionRow array{n: int, id: string, title: string, status: ConnectionStatus}
 */
final class ConnectionResolver {

  public function __construct(
    private readonly ConnectionRepository $connections,
    private readonly MountInspector $mountInspector,
    private readonly ConnectionTableRenderer $tableRenderer,
  ) {}

  /**
   * Indexed view of every known connection with its current mount status.
   *
   * @return list<ConnectionRow>
   */
  public function listAll(bool $mountedOnly = FALSE): array {
    $mounted = array_flip($this->mountInspector->mountedIds());
    $rows = [];
    $n = 1;
    foreach ($this->connections->all() as $id => $connection) {
      $is_mounted = array_key_exists($id, $mounted);
      if ($mountedOnly && !$is_mounted) {
        continue;
      }
      $rows[] = [
        'n' => $n++,
        'id' => $id,
        'title' => $connection->title,
        'status' => $is_mounted ? ConnectionStatus::Mounted : ConnectionStatus::NotMounted,
      ];
    }
    return $rows;
  }

  /**
   * Resolve a CID from an explicit argument, picking the sole connection
   * automatically, or prompting the user when several are available.
   *
   * Returns NULL if the user cancels or no connections exist.
   */
  public function resolve(SymfonyStyle $io, ?string $argument, bool $mountedOnly = FALSE): ?string {
    if ($argument !== NULL && $argument !== '') {
      if ($this->connections->exists($argument)) {
        return $argument;
      }
      $io->error(sprintf('%s is not a valid connection ID', $argument));
      return NULL;
    }

    $all = $this->connections->all();
    if ($all === []) {
      $io->writeln('No saved connections');
      return NULL;
    }

    // Single-connection shortcut — no need to query mount state.
    if (!$mountedOnly && count($all) === 1) {
      return array_key_first($all);
    }

    $rows = $this->listAll($mountedOnly);

    if ($rows === []) {
      $io->writeln($mountedOnly ? 'No mounted connections' : 'No saved connections');
      return NULL;
    }

    if (count($rows) === 1 && count($all) === 1) {
      return $rows[0]['id'];
    }

    $this->tableRenderer->render($io, $rows);

    $question = new Question('Number or ID of connection (leave empty to cancel)');
    $question->setValidator(function (?string $answer) use ($rows): ?string {
      $answer = trim((string) $answer);
      if ($answer === '' || in_array(strtolower($answer), ['c', 'cancel'], TRUE)) {
        return NULL;
      }
      $id = $this->matchRow($answer, $rows);
      if ($id === NULL) {
        throw new \RuntimeException($answer . ' is not a valid connection number or ID');
      }
      return $id;
    });

    $answer = $io->askQuestion($question);
    return is_string($answer) ? $answer : NULL;
  }

  /**
   * @param list<ConnectionRow> $rows
   */
  private function matchRow(string $answer, array $rows): ?string {
    if (ctype_digit($answer)) {
      $n = (int) $answer;
      foreach ($rows as $row) {
        if ($row['n'] === $n) {
          return $row['id'];
        }
      }
      return NULL;
    }
    foreach ($rows as $row) {
      if ($row['id'] === $answer) {
        return $row['id'];
      }
    }
    return NULL;
  }

}
