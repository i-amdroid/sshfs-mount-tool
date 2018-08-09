<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class StatusCommand extends Command {

  protected function configure() {

    $this->setName('status');
    $this->setDescription('Show status of connections');
    $this->setAliases([
      'st',
    ]);
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
      if (!match_cid($cid)) {
        $output->writeln($cid . ' is not a valid connection ID');
        return 2;
      }

      $mounts = get_mounts();
      if (in_array($cid, $mounts)) {
        $output->writeln($cid . ' is <info>mounted</info>');
      }
      else {
        $output->writeln($cid . ' is not mounted');
      }
    }
    else {
      $connections_data = get_connections_data();
      // no saved connections
      if (empty($connections_data)) {
        $output->writeln('No saved connections');
        // not an error
        return 0;
      }
      $table = gen_connections_table($connections_data, $output);
      $table->render();
    }

  }
}
