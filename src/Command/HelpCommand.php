<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Overrides Symfony's built-in HelpCommand so that `smt help` (no arg)
 * prints the application-level command list instead of describing the
 * help command itself.
 *
 * This is needed because we register a `list` command of our own (listing
 * connection properties), which shadows Symfony's built-in `list` command
 * that the default HelpCommand dispatches to when no argument is provided.
 */
#[AsCommand(
  name: 'help',
  description: 'Show help for a command, or list all commands',
)]
final class HelpCommand extends Command {

  private ?Command $target = NULL;

  /**
   * Invoked by Symfony's Application when `-h` / `--help` is used on a
   * specific command — injects that command as the one to describe.
   */
  public function setCommand(Command $command): void {
    $this->target = $command;
  }

  protected function configure(): void {
    $this->ignoreValidationErrors();
    $this
      ->setDefinition([
        new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name'),
        new InputOption(
          'format',
          NULL,
          InputOption::VALUE_REQUIRED,
          'The output format (txt, xml, json, or md)',
          'txt',
        ),
        new InputOption('raw', NULL, InputOption::VALUE_NONE, 'Output raw command help'),
      ])
      ->setHelp(<<<'EOF'
        The <info>%command.name%</info> command describes a specific command:

          <info>%command.full_name% mount</info>

        With no argument, it lists every available command.
        EOF);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $application = $this->getApplication();
    if ($application === NULL) {
      return Command::FAILURE;
    }

    $name = $input->getArgument('command_name');
    $options = [
      'format' => (string) ($input->getOption('format') ?? 'txt'),
      'raw_text' => $input->getOption('raw') === TRUE,
    ];

    $helper = new DescriptorHelper();

    if ($this->target !== NULL) {
      $helper->describe($output, $this->target, $options);
      $this->target = NULL;
      return Command::SUCCESS;
    }

    if (!is_string($name) || $name === '') {
      $helper->describe($output, $application, $options);
      return Command::SUCCESS;
    }

    $helper->describe($output, $application->find($name), $options);
    return Command::SUCCESS;
  }

}
