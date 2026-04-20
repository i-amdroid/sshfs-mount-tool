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
  name: 'unmount',
  description: 'Unmount connection',
  aliases: ['um'],
)]
final class UnmountCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp('Unmount mounted connection.')
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $cid = $this->services->resolver->resolve($io, $this->stringArgument($input, 'connection_id'), mountedOnly: TRUE);
    if ($cid === NULL) {
      return Command::SUCCESS;
    }

    $connection = $this->services->connections->find($cid);
    if ($connection === NULL) {
      $io->error(sprintf('Connection "%s" not found', $cid));
      return Command::FAILURE;
    }

    if ($output->isVerbose()) {
      $io->writeln(implode(' ', $this->services->mountCommandBuilder->buildUnmount($connection)));
    }

    $result = $this->services->mountService->unmount($connection);

    if (!$result->isSuccessful()) {
      $io->writeln(trim($result->stderr));
      return Command::FAILURE;
    }

    if ($result->stdout !== '') {
      $io->writeln($result->stdout);
    }
    else {
      $io->writeln(sprintf('%s <info>unmounted</info>', $connection->title));
    }

    return Command::SUCCESS;
  }

}
