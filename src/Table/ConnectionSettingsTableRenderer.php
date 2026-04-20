<?php

declare(strict_types=1);

namespace SSHFSMountTool\Table;

use SSHFSMountTool\Connection\Connection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ConnectionSettingsTableRenderer {

  public function render(SymfonyStyle $io, Connection $connection): void {
    $table = new Table($io);
    $table->setHeaders(['Setting', 'Value']);

    $rows = [
      ['id', $connection->id],
      ['title', $connection->title],
      ['server', $connection->server],
      ['port', $connection->port !== NULL ? (string) $connection->port : ''],
      ['user', $connection->user ?? ''],
      ['password', $connection->password !== NULL ? '[password]' : ''],
      ['key', $connection->key ?? ''],
      ['mount', $connection->mount ?? ''],
      ['remote', $connection->remote ?? ''],
      ['options', implode(',', $connection->options)],
      ['ssh_options', implode(',', $connection->sshOptions)],
    ];

    foreach ($rows as $row) {
      $table->addRow($row);
    }

    $table->setStyle('compact');
    $style = $table->getStyle();
    $style->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
    $style->setCellRowContentFormat('%s    ');
    $style->setVerticalBorderChars('');

    $table->render();
  }

}
