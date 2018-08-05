<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

class MountCommand extends Command {

  protected function configure() {

    $this->setName('mount');
    $this->setDescription('Mount connection');
    $this->setHelp('Mount previously saved connection');
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the conntection');
    $this->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Provide password');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    global $preferences;

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
        $question = new Question('Number or ID of connection [<comment>cancel</comment>]: ');
        $question->setValidator(function($answer) use ($connections_data) {
          if ($answer == '' || $answer == 'c' || $answer == 'C' || $answer == 'cancel' || $answer == 'Cancel' || $answer == 'CANCEL') {
            // return from callback without $cid
            return;
          }
          $cid = validate_answer_as_connection($answer, $connections_data);
          if (!$cid) {
            throw new \RuntimeException(
              $answer . ' is not a valid connection number or ID'
            );
          }
          else {
            return $cid;
          }
        });
        
        $cid = $helper->ask($input, $output, $question);

        if (!$cid) {
          // canceled
          return 0;
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
    //$output->writeln($cmd);
    $connection_settings = get_connection_settings($cid);

    // check existing of mount point and create if needed
    if (substr($connection_settings['mount'], 0, 1) == '~') {
      $mount_dir = $preferences['home_path'] . substr($connection_settings['mount'], 1);
    }
    else {
      $mount_dir = $connection_settings['mount'];
    }
    if (!is_dir($mount_dir)) {
      mkdir($mount_dir, 0777, TRUE);
    }

    // Verbose mesages
    if ($output->isVerbose()) {
      $masked_cmd = gen_mount_cmd($cid, $password, TRUE);
      $output->writeln($masked_cmd);
    }

    // Command execution
    $process = new Process($cmd);
    $process->run();

    // Normal mmesages
    if (!$process->isSuccessful()) {
      // throw new ProcessFailedException($process);
      $output->writeln($process->getErrorOutput());
    }
    else {
      if ($process->getOutput()) {
        $output->writeln($process->getOutput());
      }
      else {
        $success_message = '';
        if (isset($connection_settings['user'])) {
          $success_message .= $connection_settings['user'] . '@';
        }
        $success_message .= $connection_settings['server'] . ' ' . '<info>mounted</info>' . ' to ' . $connection_settings['mount'];
        $output->writeln($success_message);
      }
    }

  }
}
