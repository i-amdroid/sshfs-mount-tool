<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConfigCommand extends Command {

  protected function configure() {
    $this->setName('config');
    $this->setDescription('Open config file');
    $this->setAliases(['cfg']);
    $this->setHelp('Open config file. By default used connection settings file.');
    $this->addOption('settings', 's', InputOption::VALUE_NONE, 'Open SMT settings file (preferences)');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    global $preferences;

    if ($input->getOption('settings')) {
      $config_file = $preferences['user_preferences_file'];
    }
    else {
      $config_file = get_config_file();
    }

    $cmd = [$preferences['editor'], $config_file];

    // Command execution.
    $process = new Process($cmd);
    $process->setTty(TRUE);
    $process->run();

    return Command::SUCCESS;
  }

}
