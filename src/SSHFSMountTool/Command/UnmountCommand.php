<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class UnmountCommand extends Command {

  protected function configure() {

    $this->setName('unmount');
    $this->setDescription('Unmount connection');
    $this->setHelp('Unmount mounted connection');
    $this->setAliases([
      'um',
    ]);
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the conntection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $mounted_only = TRUE;
    $connections_data = get_connections_data($mounted_only);

    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
      if (!match_cid($cid, $connections_data)) {
        $output->writeln($cid . ' is not a mounted connection ID');
        return 2;
      }
    }
    // cid not provided
    else {

      // no saved connections
      if (empty($connections_data)) {
        $output->writeln('No mounted connections');
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
        $question = new Question('Number or ID of connection [Enter, c to cancel]: ');
        $answer = $helper->ask($input, $output, $question);
        if ($answer == '' || $answer == 'c' || $answer == 'C' || $answer == 'cancel' || $answer == 'Cancel' || $answer == 'CANCEL') {
          return 0;
        }
        // Emulate $question->setValidator for pass additional parameter to validator
        $cid = validate_answer_as_connection($answer, $connections_data);
        if (!$cid) {
          $output->writeln($answer . ' is not a valid connection number or ID');
          return 2;
        }
      }
    }
  
    $cmd = gen_unmount_cmd($cid);
    // $output->writeln($cmd);
    $connection_settings = get_connection_settings($cid);

    $success_message = '';
    if (isset($connection_settings['user'])) {
      $success_message .= $connection_settings['user'] . '@';
    }
    $success_message .= $connection_settings['server'] . ' ' . '<info>unmounted</info>';

    // @todo if verbose
    /*
    if ($verbose) {
      echo $cmd . PHP_EOL;
    }
    */

    $run = run_cmd($cmd, $success_message);

    $output->writeln($run);

  }
}
