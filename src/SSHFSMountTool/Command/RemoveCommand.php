<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RemoveCommand extends Command {

  protected function configure() {

    $this->setName('remove');
    $this->setDescription('Remove connection');
    $this->setAliases([
      'rm',
    ]);
    $this->setHelp('Remove previously saved connection');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // canceled
      return 0;
    }

    remove_connection_settings($cid);

    // here can be only success removing
    $output->writeln('<info>Connection removed</info>');

  }
}
