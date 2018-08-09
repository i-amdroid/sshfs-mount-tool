<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

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
      // canceled
      return 0;
    }

    $connection_settings = get_connection_settings($cid);

    if (isset($connection_settings['mount'])) {
      if (substr($connection_settings['mount'], 0, 1) == '~') {
        $path = $preferences['home_path'] . substr($connection_settings['mount'], 1);
      }
      else {
        $path = $connection_settings['mount'];
      }
      $cmd = 'cd ' . $path;
      run_terminal_cmd($cmd);
    }
    else {
      $output->writeln('No mount point for ' . $cid . ' set');
    }

  }
}
