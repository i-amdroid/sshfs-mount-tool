<?php

/**
 * @file
 * Provides main functionality.
 */

// Include composer dependencies.
// Manual installation.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}
// Installation with composer.
elseif (file_exists(__DIR__ . '/../../autoload.php')) {
  require_once __DIR__ . '/../../autoload.php';
}
else {
  echo 'You must set up the project dependencies using "composer install"' . PHP_EOL;
}
// Include base project config and functions.
require __DIR__ . '/includes/bootstrap.inc';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Registering commands.
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
  'SSHFSMountTool\Command\CdCommand',
  'SSHFSMountTool\Command\SshCommand',
];
$command_names = ['completion'];
$command_aliases = [];
$commands = [];

foreach ($command_list as $key => $command_class) {
  $command = new $command_class();
  $commands[] = $command;
  $command_names[] = $command->getname();
  $command_aliases = array_merge($command->getAliases(), $command_aliases);
}

$aliases = array_merge($command_names, $command_aliases);

// Emulate $app->setDefaultCommand for handling arguments.
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

// Define app.
$app = new Application('SSHFS Mount Tool', get_version());

// Add global option to the app.
$app->getDefinition()->addOption(
  new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global connections')
);

// Add info command as app option.
$app->getDefinition()->addOption(
  new InputOption('info', 'i', InputOption::VALUE_NONE, 'Display information about dependencies')
);

// Handle global, info and help options.
$dispatcher = new EventDispatcher();
$app->setDispatcher($dispatcher);

$dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {

  global $preferences;
  $input = $event->getInput();

  if ($input->getOption('global')) {
    $preferences['global'] = TRUE;
  }

  if ($input->getOption('info')) {
    $command = $event->getCommand();
    $application = $command->getApplication();
    $info_command = $application->find('info');
    $output = $event->getOutput();

    $exitCode = $info_command->run($input, $output);
    exit($exitCode);

    // TODO: find proper way to exit without running default command after
    // return 0;

  }

  // Seems like something broken after overriding help command
  // and command like "smt mount -h" don't work anymore, so handle it manually. 
  // Not sure that this is the best way, but it works now.
  if ($input->getOption('help')) {
    $command = $event->getCommand();
    $application = $command->getApplication();
    $help_command = $application->find('help');
    // $input->setArgument('command_name', $input->getArgument('command'));
    $new_input = new ArrayInput(array('command_name' => $input->getArgument('command')));
    $output = $event->getOutput();

    $exitCode = $help_command->run($new_input, $output);
    exit($exitCode);

    // TODO: find proper way to exit without running default command after
    // return 0;

  }

});

$app->addCommands($commands);
$app->setDefaultCommand('mount');

$app->run();
