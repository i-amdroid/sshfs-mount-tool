<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SshCommand extends Command {

  protected function configure() {
    $this->setName('ssh');
    $this->setDescription('Launch SSH session');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
    $this->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Provide password');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    global $preferences;

    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // Canceled.
      return Command::SUCCESS;
    }

    if ($input->getOption('password')) {
      $password = $input->getOption('password');
    }
    else {
      $password = FALSE;
    }

    $connection_settings = get_connection_settings($cid);
    $cmd = '';

    // Override password.
    if ($password) {
      $connection_settings['password'] = $password;
    }

    // Password auth.
    if ($connection_settings['password']) {
      $cmd .= 'sshpass -p ' . $connection_settings['password'] . ' ';
    }

    $cmd .= 'ssh ';
    // SSH options.
    $ssh_options = $preferences['default_ssh_options'];
    if (isset($connection_settings['ssh_options'])) {
      $ssh_options = array_merge($ssh_options, $connection_settings['ssh_options']);
    }
    $cmd .= implode(' ', $ssh_options) . ' ';

    // User and server.
    if (isset($connection_settings['user'])) {
      $cmd .= $connection_settings['user'] . '@';
    }
    $cmd .= $connection_settings['server'];

    // Port.
    if ($connection_settings['port']) {
      $cmd .= ' -p ' . $connection_settings['port'];
    };

    $terminal_cmd = gen_terminal_cmd($cmd);

    // Command execution.
    $process = Process::fromShellCommandline($terminal_cmd);
    $process->run();

    // Normal massages.
    if (!$process->isSuccessful()) {
      // throw new ProcessFailedException($process);
      $output->writeln($process->getErrorOutput());
    }

    return Command::SUCCESS;
  }

}
