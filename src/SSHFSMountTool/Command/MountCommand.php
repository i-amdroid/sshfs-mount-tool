<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class MountCommand extends Command {

  protected function configure() {

    $this->setName('mount');
    $this->setDescription('Mount connection');
    $this->setHelp('Mount previously saved connection');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the conntection');
    $this->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Provide password');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
      if (!match_cid($cid)) {
        $output->writeln($cid . ' is not a valid connection ID');
        return 2;
      }
    }
    // cid not provided
    else {
      $connections_data = get_connections_data();

      // no saved connections
      if (empty($connections_data)) {
        $output->writeln('No saved connections');
        // not an error
        return 0;
      }
      // one connection
      elseif (count($connections_data) == 1) {
        $cid = $connections_data[0]['cid'];
      }
      // multiple connections
      else {
        $table = gen_connections_table($connections_data, $output);
        $table->render();

        $helper = $this->getHelper('question');
        $question = new Question('Number or ID of connection: ');
        $answer = $helper->ask($input, $output, $question);
        // Emulate $question->setValidator for pass additional parameter to validator
        $cid = validate_answer_as_connection($answer, $connections_data);
        if (!$cid) {
          $output->writeln($answer . ' is not a valid connection number or ID');
          return 2;
        }
      }
    }

    if ($input->getOption('password')) {
      $password = $input->getOption('password');
    }
    else {
      $password = FALSE;
    }
    
    $cmd = gen_mount_cmd($cid, $password);
    $output->writeln($cmd);

  }
}
