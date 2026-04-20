<?php

declare(strict_types=1);

namespace SSHFSMountTool\Table;

use SSHFSMountTool\Connection\ConnectionStatus;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @phpstan-type ConnectionRow array{n: int, id: string, title: string, status: ConnectionStatus}
 */
final class ConnectionTableRenderer {

  /**
   * @param list<ConnectionRow> $rows
   */
  public function render(SymfonyStyle $io, array $rows): void {
    $table = new Table($io);
    $table->setHeaders(['#', 'ID', 'Title', 'Status']);

    foreach ($rows as $row) {
      $cells = [
        (string) $row['n'],
        $row['id'],
        $row['title'],
        $row['status']->value,
      ];
      if ($row['status'] === ConnectionStatus::Mounted) {
        $cells = array_map(static fn (string $cell): string => '<info>' . $cell . '</info>', $cells);
      }
      $table->addRow($cells);
    }

    $table->setStyle('compact');
    $style = $table->getStyle();
    $style->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
    $style->setCellRowContentFormat('%s  ');
    $style->setVerticalBorderChars('');

    $table->render();
  }

}
