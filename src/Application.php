<?php

declare(strict_types=1);

namespace SSHFSMountTool;

use SSHFSMountTool\Command\AddCommand;
use SSHFSMountTool\Command\CdCommand;
use SSHFSMountTool\Command\ConfigCommand;
use SSHFSMountTool\Command\HelpCommand;
use SSHFSMountTool\Command\InfoCommand;
use SSHFSMountTool\Command\ListCommand;
use SSHFSMountTool\Command\MountCommand;
use SSHFSMountTool\Command\RemoveCommand;
use SSHFSMountTool\Command\ShellInitCommand;
use SSHFSMountTool\Command\SshCommand;
use SSHFSMountTool\Command\StatusCommand;
use SSHFSMountTool\Command\UnmountCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends BaseApplication {

  public const string VERSION = '5.0.0';

  public function __construct(
    private readonly Services $services,
  ) {
    parent::__construct('SSHFS Mount Tool', self::VERSION);

    $this->registerCommands();

    $this->getDefinition()->addOption(
      new InputOption('global', 'g', InputOption::VALUE_NONE, 'Use global connections'),
    );
    $this->getDefinition()->addOption(
      new InputOption('info', 'i', InputOption::VALUE_NONE, 'Display information about dependencies'),
    );

    $this->setDefaultCommand('mount');
  }

  /**
   * Ensures `smt <cid>` works by splicing the default `mount` command when
   * the first argv token matches neither a known command/alias nor a global
   * option. Also handles the app-level --info shortcut.
   */
  public function run(?InputInterface $input = NULL, ?OutputInterface $output = NULL): int {
    if ($input === NULL) {
      $argv = $_SERVER['argv'] ?? [];
      $argv = $this->spliceDefaultCommand($argv);
      $argv = $this->rewriteInfoShortcut($argv);
      $input = new ArgvInput($argv);
    }

    return parent::run($input, $output);
  }

  private function registerCommands(): void {
    foreach ([
      new MountCommand($this->services),
      new UnmountCommand($this->services),
      new AddCommand($this->services),
      new RemoveCommand($this->services),
      new ListCommand($this->services),
      new StatusCommand($this->services),
      new ConfigCommand($this->services),
      new InfoCommand($this->services),
      new CdCommand($this->services),
      new SshCommand($this->services),
      new ShellInitCommand(),
      new HelpCommand(),
    ] as $command) {
      $this->addCommand($command);
    }
  }

  /**
   * @param list<string> $argv
   * @return list<string>
   */
  private function spliceDefaultCommand(array $argv): array {
    $first = $argv[1] ?? NULL;
    if ($first === NULL) {
      return $argv;
    }
    // Options (--foo, -x) are forwarded untouched.
    if (str_starts_with($first, '-')) {
      return $argv;
    }
    // Known command names or aliases — run them as-is.
    foreach ($this->all() as $command) {
      if ($command->getName() === $first || in_array($first, $command->getAliases(), TRUE)) {
        return $argv;
      }
    }
    // 'completion' is a built-in command — leave it alone.
    if ($first === 'completion' || $first === 'help') {
      return $argv;
    }
    // Otherwise assume the first token is a connection ID — prepend 'mount'.
    array_splice($argv, 1, 0, ['mount']);
    return $argv;
  }

  /**
   * Turn `smt --info` / `smt -i` into `smt info` before Symfony dispatches.
   *
   * @param list<string> $argv
   * @return list<string>
   */
  private function rewriteInfoShortcut(array $argv): array {
    $first = $argv[1] ?? NULL;
    if ($first !== NULL && in_array($first, ['-i', '--info'], TRUE)) {
      $argv[1] = 'info';
    }
    return $argv;
  }

  /**
   * Check `--global`/`-g` in argv before Services is constructed so that
   * `Preferences` captures the correct choice of config file up front.
   *
   * @param list<string> $argv
   */
  public static function isGlobalFlagSet(array $argv): bool {
    foreach ($argv as $arg) {
      if ($arg === '--global' || $arg === '-g') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
