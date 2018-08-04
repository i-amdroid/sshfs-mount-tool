<?php

/**
 * @file
 * Provides main functionality.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/bootstrap.inc';

use SSHFSMountTool\Command\MountCommand;
use SSHFSMountTool\Command\UnmountCommand;
use SSHFSMountTool\Command\StatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Registering commands
$command_names = [];
$command_aliases = [];
$default_commands = [
  'help',
  'list',
];
$commands = [];

// Mount command
$mount_command = new MountCommand();
$commands[] = $mount_command;
$command_names[] = $mount_command->getname();
$command_aliases = array_merge($mount_command->getAliases(), $command_aliases);

// Unmount command
$unmount_command = new UnmountCommand();
$commands[] = $unmount_command;
$command_names[] = $unmount_command->getname();
$command_aliases = array_merge($unmount_command->getAliases(), $command_aliases);

// Status command
$status_command = new StatusCommand();
$commands[] = $status_command;
$command_names[] = $status_command->getname();
$command_aliases = array_merge($status_command->getAliases(), $command_aliases);

$aliases = array_merge($command_names, $command_aliases, $default_commands);

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
$app = new Application('SSHFS Mount Tool', '1.1.0');

// Add global option to the app
$app->getDefinition()->addOption(
  new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global connections')
);
$dispatcher = new EventDispatcher();
$app->setDispatcher($dispatcher);

$dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
  global $preferences;
  // gets the input instance
  $input = $event->getInput();
  if ($input->getOption('global')) {
    $preferences['global'] = TRUE;
  }
});

$app->addCommands($commands);
// $app->setDefaultCommand($mount_command->getName());

$app->run();


