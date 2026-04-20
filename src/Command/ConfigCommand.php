<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'config',
  description: 'Open config file',
  aliases: ['cfg'],
)]
final class ConfigCommand extends AbstractCommand {

  protected function configure(): void {
    $this
      ->setHelp('Open config file. By default the connections file is opened.')
      ->addOption('settings', 's', InputOption::VALUE_NONE, 'Open SMT settings file (preferences)');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $file = $this->boolOption($input, 'settings')
      ? $this->services->preferences->userPreferencesFile
      : $this->services->preferences->activeConfigFile();

    $editor = $this->services->preferences->editor;
    $exit_code = $this->services->processRunner->runTty(['sh', '-c', $editor . ' ' . escapeshellarg($file)]);
    return $exit_code === 0 ? Command::SUCCESS : Command::FAILURE;
  }

}
