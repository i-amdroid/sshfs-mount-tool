<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends Command {

  private $command;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->ignoreValidationErrors();

    $this
      ->setName('help')
      ->setDefinition([
        new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', FALSE),
        new InputOption('format', NULL, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
        new InputOption('raw', NULL, InputOption::VALUE_NONE, 'To output raw command help'),
      ])
      ->setDescription('Displays help for a command')
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% mount</info>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml mount</info>

To display the list of available commands, please use the <info>help</info> command without arguments.
EOF
      );
  }

  public function setCommand(Command $command) {
    $this->command = $command;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    if (!$input->getArgument('command_name')) {

      $helper = new DescriptorHelper();
      $helper->describe($output, $this->getApplication(), [
        'format' => $input->getOption('format'),
        'raw_text' => $input->getOption('raw'),
      ]);

    }
    else {
      if (NULL === $this->command) {
        $this->command = $this->getApplication()
          ->find($input->getArgument('command_name'));
      }

      $helper = new DescriptorHelper();
      $helper->describe($output, $this->command, [
        'format' => $input->getOption('format'),
        'raw_text' => $input->getOption('raw'),
      ]);

      $this->command = NULL;
    }

  }

}
