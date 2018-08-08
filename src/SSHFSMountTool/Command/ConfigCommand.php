<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConfigCommand extends Command {

  protected function configure() {

    $this->setName('config');
    $this->setDescription('Open config file');
    $this->setAliases([
      'cfg',
    ]);

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    global $preferences;
    $config_file = get_config_file();
    $cmd = $preferences['editor'] . ' ' . $config_file;

    // Command execution
    $process = new Process($cmd);
    $process->setTty(true);
    $process->run();

  }
}
