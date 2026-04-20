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
  name: 'remove',
  description: 'Remove connection',
  aliases: ['rm'],
)]
final class RemoveCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp('Remove previously saved connection.')
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $cid = $this->services->resolver->resolve($io, $this->stringArgument($input, 'connection_id'));
    if ($cid === NULL) {
      return Command::SUCCESS;
    }

    $this->services->connections->remove($cid);
    $io->writeln('<info>Connection removed</info>');
    return Command::SUCCESS;
  }

}
