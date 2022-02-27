<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

class UnmountCommand extends Command {

  protected function configure() {

    $this->setName('unmount');
    $this->setDescription('Unmount connection');
    $this->setHelp('Unmount mounted connection');
    $this->setAliases([
      'um',
    ]);
    $this->addArgument('connection_id', InputArgument::OPTIONAL, 'ID of the connection');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $mounted_only = TRUE;
    $connections_data = get_connections_data($mounted_only);

    if ($input->getArgument('connection_id')) {
      $cid = $input->getArgument('connection_id');
      if (!match_cid($cid, $connections_data)) {
        $output->writeln($cid . ' is not a mounted connection ID');
        return Command::INVALID;
      }
    }
    // cid not provided
    else {

      // no mounted connections
      if (empty($connections_data)) {
        $output->writeln('No mounted connections');
        // not an error
        return Command::SUCCESS;
      }
      // one mounted connection and only one connection in config file
      elseif (count($connections_data) == 1 && count(get_settings('connections')) == 1) {
        $cid = $connections_data[0]['cid'];
      }
      // multiple mounted connections or multiple connections in config file
      else {
        $table = gen_connections_table($connections_data, $output);
        $table->render();

        $helper = $this->getHelper('question');
        $question = new Question('Number or ID of connection [<comment>cancel</comment>]: ');
        $question->setValidator(function ($answer) use ($connections_data) {
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
          return Command::SUCCESS;
        }
      }
    }

    $cmd = gen_unmount_cmd($cid);
    $connection_settings = get_connection_settings($cid);

    // Verbose messages
    if ($output->isVerbose()) {
      $output->writeln($cmd);
    }

    // Command execution
    $process = Process::fromShellCommandline($cmd);
    $process->run();

    // Normal massages
    if (!$process->isSuccessful()) {
      // throw new ProcessFailedException($process);
      $output->writeln($process->getErrorOutput());
    }
    else {
      if ($process->getOutput()) {
        $output->writeln($process->getOutput());
      }
      else {
        $success_message = $connection_settings['title'] . ' ' . '<info>unmounted</info>';
        $output->writeln($success_message);
      }
    }

    return Command::SUCCESS;

  }
}
