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

    $helper = $this->getHelper('question');
    $cid = cid_resolver($input, $output, $helper);

    if (!$cid) {
      // canceled
      return 0;
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

    // Verbose messages
    if ($output->isVerbose()) {
      $masked_cmd = gen_mount_cmd($cid, $password, TRUE);
      $output->writeln($masked_cmd);
    }

    // Command execution
    $process = new Process($cmd);
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
        $success_message = $connection_settings['title'] . ' ' . '<info>mounted</info>' . ' to ' . $connection_settings['mount'];
        $output->writeln($success_message);
      }
    }

  }
}