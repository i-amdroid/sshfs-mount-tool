<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command {

  protected function configure() {
    $this->setName('list');
    $this->setDescription('List connection properties');
    $this->setAliases(['ls']);
    $this->setHelp('List previously saved connection properties');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // Canceled.
      return Command::SUCCESS;
    }

    $connection_settings = get_connection_settings($cid);

    $table = gen_connection_settings_table($cid, $connection_settings, $output);
    $table->render();

    return Command::SUCCESS;
  }

}
