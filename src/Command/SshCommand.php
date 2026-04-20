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
  name: 'ssh',
  description: 'Launch SSH session',
)]
final class SshCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp(
        'Launch an SSH session to the connection.'
        . PHP_EOL
        . 'By default ssh runs in the current terminal tab. Use <comment>--new-tab</comment> to spawn a new one.',
      )
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection')
      ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Provide password')
      ->addOption('new-tab', 't', InputOption::VALUE_NONE, 'Run ssh in a new terminal tab instead of the current one');
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

    if ($this->boolOption($input, 'new-tab')) {
      $result = $this->services->sshSessionLauncher->launchInNewTab($connection, $password);
      if (!$result->isSuccessful()) {
        $io->writeln(trim($result->stderr));
        return Command::FAILURE;
      }
      return Command::SUCCESS;
    }

    // Same-tab: ssh owns the TTY until it exits. Propagate its exit code.
    return $this->services->sshSessionLauncher->launchInCurrentTab($connection, $password);
  }

}
