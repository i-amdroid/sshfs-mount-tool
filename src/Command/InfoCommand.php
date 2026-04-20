<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use SSHFSMountTool\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'info',
  description: 'Display information about dependencies',
)]
final class InfoCommand extends AbstractCommand {

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $io->writeln(sprintf('SSHFS Mount Tool <info>%s</info>', Application::VERSION));
    $io->newLine();

    $rows = [
      ['PHP', PHP_VERSION],
      ['SSHFS', $this->detectSshfsVersion() ?? '<comment>not found</comment>'],
      ['sshpass', $this->detectSshpassVersion() ?? '<comment>not found</comment>'],
    ];

    $table = new Table($io);
    $table->setHeaders(['Dependency', 'Version']);
    foreach ($rows as $row) {
      $table->addRow($row);
    }
    $table->setStyle('compact');
    $style = $table->getStyle();
    $style->setCellHeaderFormat('<fg=cyan;options=bold>%s</>');
    $style->setCellRowContentFormat('%s    ');
    $style->setVerticalBorderChars('');
    $table->render();

    return Command::SUCCESS;
  }

  private function detectSshfsVersion(): ?string {
    $result = $this->services->processRunner->run(['sshfs', '--version']);
    $combined = $result->stdout . PHP_EOL . $result->stderr;
    $matches = [];
    if (preg_match('/SSHFS version\s+(\S+)/i', $combined, $matches) === 1) {
      return $matches[1];
    }
    return NULL;
  }

  private function detectSshpassVersion(): ?string {
    $result = $this->services->processRunner->run(['sshpass', '-V']);
    $combined = $result->stdout . PHP_EOL . $result->stderr;
    $matches = [];
    if (preg_match('/sshpass\s+(\S+)/i', $combined, $matches) === 1) {
      return $matches[1];
    }
    return NULL;
  }

}
