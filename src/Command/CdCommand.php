<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CdCommand extends Command {

  protected function configure() {
    $this->setName('cd');
    $this->setDescription('Change directory to connection mount directory');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    global $preferences;
    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // Canceled.
      return Command::SUCCESS;
    }

    $connection_settings = get_connection_settings($cid);

    if (isset($connection_settings['mount'])) {
      if (str_starts_with($connection_settings['mount'], '~')) {
        $path = $preferences['home_path'] . substr($connection_settings['mount'], 1);
      }
      else {
        $path = $connection_settings['mount'];
      }
      $cmd = 'cd ' . $path;
      $terminal_cmd = gen_terminal_cmd($cmd);

      // Command execution.
      $process = Process::fromShellCommandline($terminal_cmd);
      $process->run();

      // Normal massages.
      if (!$process->isSuccessful()) {
        // throw new ProcessFailedException($process);
        $output->writeln($process->getErrorOutput());
      }
    }
    else {
      $output->writeln('No mount point for ' . $cid . ' set');
    }

    return Command::SUCCESS;
  }
}
