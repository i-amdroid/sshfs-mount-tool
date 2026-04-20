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
  name: 'list',
  description: 'List connection properties',
  aliases: ['ls'],
)]
final class ListCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp('List previously saved connection properties.')
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $cid = $this->services->resolver->resolve($io, $this->stringArgument($input, 'connection_id'));
    if ($cid === NULL) {
      return Command::SUCCESS;
    }

    $connection = $this->services->connections->find($cid);
    if ($connection === NULL) {
      $io->error(sprintf('Connection "%s" not found', $cid));
      return Command::FAILURE;
    }

    $this->services->settingsTableRenderer->render($io, $connection);
    return Command::SUCCESS;
  }

}
