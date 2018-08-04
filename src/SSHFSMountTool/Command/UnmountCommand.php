<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UnmountCommand extends Command {

  protected function configure() {

    $this->setName('unmount');
    $this->setDescription('Unmount connection');
    $this->setHelp('Unmount mounted connection');
    $this->setAliases([
      'um',
    ]);
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the conntection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
    }
    else {
      $cid = 'none';
    }
  
    $output->writeln('<Unmounting ' . $cid . '> ');

  }
}
