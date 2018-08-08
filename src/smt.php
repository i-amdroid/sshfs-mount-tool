<?php

/**
 * @file
 * Provides main functionality.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/bootstrap.inc';

use SSHFSMountTool\Command\MountCommand;
use SSHFSMountTool\Command\UnmountCommand;
use SSHFSMountTool\Command\AddCommand;
use SSHFSMountTool\Command\RemoveCommand;
use SSHFSMountTool\Command\ListCommand;
use SSHFSMountTool\Command\StatusCommand;
use SSHFSMountTool\Command\ConfigCommand;
use SSHFSMountTool\Command\InfoCommand;
use SSHFSMountTool\Command\HelpCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Registering commands
$command_list = [
  'SSHFSMountTool\Command\MountCommand',
  'SSHFSMountTool\Command\UnmountCommand',
  'SSHFSMountTool\Command\AddCommand',
  'SSHFSMountTool\Command\RemoveCommand',
  'SSHFSMountTool\Command\ListCommand',
  'SSHFSMountTool\Command\StatusCommand',
  'SSHFSMountTool\Command\ConfigCommand',
  'SSHFSMountTool\Command\InfoCommand',
  'SSHFSMountTool\Command\HelpCommand',
];
$command_names = [];
$command_aliases = [];
$commands = [];

foreach ($command_list as $key => $command_class) {
  $command = new $command_class();
  $commands[] = $command;
  $command_names[] = $command->getname();
  $command_aliases = array_merge($command->getAliases(), $command_aliases);
}

$aliases = array_merge($command_names, $command_aliases);

// Emulate $app->setDefaultCommand for handling arguments
$first_arg_is_command = FALSE;

if (isset($_SERVER['argv'][1])) {
  foreach ($aliases as $key => $alias) {
    if ($alias == $_SERVER['argv'][1]) {
      $first_arg_is_command = TRUE;
      break;
    }
  }
}

if (!$first_arg_is_command) {
  array_splice($_SERVER['argv'], 1, 0, 'mount');
}

// Define app
$app = new Application('SSHFS Mount Tool', get_version());

// Add global option to the app
$app->getDefinition()->addOption(
  new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global connections')
);
$dispatcher = new EventDispatcher();
$app->setDispatcher($dispatcher);

$app->getDefinition()->addOption(
  new InputOption('info', 'i', InputOption::VALUE_NONE, 'Show information about dependencies')
);

$dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
  global $preferences;
  // gets the input instance
  $input = $event->getInput();
  if ($input->getOption('global')) {
    $preferences['global'] = TRUE;
  }

  if ($input->getOption('info')) {
    $command = $event->getCommand();
    $application = $command->getApplication();
    // print_r($application->getDefinition());
    $info_command = $application->find('info');
    $output = $event->getOutput();
    $info_command->run($input, $output);
    // return 0;
    // @todo fing proper way to exit without running default command after
    exit(0);
  }

});

$app->addCommands($commands);
// $app->setDefaultCommand($mount_command->getName());

$app->run();


