<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'mount',
  description: '<comment>Mount connection (default)</comment>',
)]
final class MountCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp(
        'Mount previously saved connection.'
        . PHP_EOL
        . 'This is the default command and may be used without specifying "mount".',
      )
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection')
      ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Provide password');
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

    $password = $this->stringOption($input, 'password');

    if ($output->isVerbose()) {
      $command = $this->services->mountCommandBuilder->buildMount($connection, $password);
      $io->writeln($this->services->mountCommandBuilder->displayMount($command));
    }

    $result = $this->services->mountService->mount($connection, $password);

    if (!$result->isSuccessful()) {
      $io->writeln(trim($result->stderr));
      return Command::FAILURE;
    }

    if ($result->stdout !== '') {
      $io->writeln($result->stdout);
    }
    else {
      $io->writeln(sprintf(
        '%s <info>mounted</info> to %s',
        $connection->title,
        $connection->mount,
      ));
    }

    return Command::SUCCESS;
  }

}
