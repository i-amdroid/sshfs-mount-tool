<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class AddCommand extends Command {

  protected function configure() {

    $this->setName('add');
    $this->setDescription('Add connection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    global $preferences;
    $connection_settings = [];

    $helper = $this->getHelper('question');
    $question_server = new Question('Server: ');
    $question_server->setValidator(function ($value) {
      if (trim($value) == '') {
        throw new \Exception('Server is required');
      }

      return $value;
    });
    $question_server->setMaxAttempts(NULL);
    $server = $helper->ask($input, $output, $question_server);

  }
}
