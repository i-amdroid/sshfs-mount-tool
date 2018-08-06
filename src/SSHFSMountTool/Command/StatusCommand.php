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
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the conntection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    // @todo add display status of one connection
    /*
    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
    }
    else {
      $cid = 'none';
    }
    */

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
