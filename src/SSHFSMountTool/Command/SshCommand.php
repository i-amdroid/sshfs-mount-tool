<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SshCommand extends Command {

  protected function configure() {

    $this->setName('ssh');
    $this->setDescription('Launch SSH session');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // canceled
      return 0;
    }

    $connection_settings = get_connection_settings($cid);
    $cmd = 'ssh ';
    if (isset($connection_settings['user'])) {
      $cmd .= $connection_settings['user'] . '@';
    }
    $cmd .= $connection_settings['server'];
    
    run_terminal_cmd($cmd);

  }
}
