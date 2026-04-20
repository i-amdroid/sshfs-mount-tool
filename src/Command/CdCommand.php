<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'cd',
  description: 'Change directory to connection mount directory',
)]
final class CdCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp(
        'Change directory to the connection\'s mount directory.'
        . PHP_EOL
        . 'Without options a new terminal tab is spawned (a child process cannot change the'
        . ' parent shell\'s directory).'
        . PHP_EOL
        . 'With <comment>--eval</comment> the tool prints a quoted <info>cd</info> command to stdout; the shell wrapper'
        . ' installed via <info>smt shell-init</info> wires this up so <info>smt cd &lt;id&gt;</info> changes the current tab.',
      )
      ->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection')
      ->addOption('eval', 'e', InputOption::VALUE_NONE, 'Print a `cd` command to stdout for shell eval');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $eval_mode = $this->boolOption($input, 'eval');

    // In eval mode all UI goes to stderr so stdout carries only the cd command.
    $ui = $eval_mode && $output instanceof ConsoleOutputInterface
      ? $output->getErrorOutput()
      : $output;
    $io = new SymfonyStyle($input, $ui);

    $cid = $this->services->resolver->resolve($io, $this->stringArgument($input, 'connection_id'));
    if ($cid === NULL) {
      return Command::SUCCESS;
    }

    $connection = $this->services->connections->find($cid);
    if ($connection === NULL || $connection->mount === NULL) {
      $io->error(sprintf('No mount point for %s set', $cid));
      return Command::FAILURE;
    }

    $path = $this->services->pathExpander->expand($connection->mount);

    if ($eval_mode) {
      $output->writeln('cd ' . escapeshellarg($path));
      return Command::SUCCESS;
    }

    $result = $this->services->terminalLauncher->launch('cd ' . escapeshellarg($path));
    if (!$result->isSuccessful()) {
      $io->writeln(trim($result->stderr));
      return Command::FAILURE;
    }

    return Command::SUCCESS;
  }

}
