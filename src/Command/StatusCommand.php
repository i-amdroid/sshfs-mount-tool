<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'status',
  description: 'Show status of connections',
  aliases: ['st'],
)]
final class StatusCommand extends AbstractCommand {

  protected function configure(): void {
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $argument = $this->stringArgument($input, 'connection_id');

    if ($argument !== NULL) {
      if (!$this->services->connections->exists($argument)) {
        $io->error(sprintf('%s is not a valid connection ID', $argument));
        return Command::INVALID;
      }
      $mounted = in_array($argument, $this->services->mountInspector->mountedIds(), TRUE);
      $io->writeln(
        $mounted
          ? sprintf('%s is <info>mounted</info>', $argument)
          : sprintf('%s is not mounted', $argument),
      );
      return Command::SUCCESS;
    }

    $rows = $this->services->resolver->listAll();
    if ($rows === []) {
      $io->writeln('No saved connections');
      return Command::SUCCESS;
    }
    $this->services->tableRenderer->render($io, $rows);
    return Command::SUCCESS;
  }

}
