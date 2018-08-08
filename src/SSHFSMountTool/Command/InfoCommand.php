<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

class InfoCommand extends Command {

  protected function configure() {

    $this->setName('info');
    $this->setDescription('Display information about dependencies');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    // smt version
    $smt_version = get_version();
    $output->writeln('SSHFS Mount Tool <info>'. $smt_version . '</info>');

    // SSHFS version
    $sshfs = new Process('sshfs --version 2> /dev/null');
    $sshfs->run();
    $sshfs_version = substr($sshfs->getOutput(), strpos($sshfs->getOutput(), 'SSHFS version ') + strlen('SSHFS version '), 4);
    $sshfs_version = str_replace(PHP_EOL, '', $sshfs_version);
    $output->writeln('SSHFS <info>'. $sshfs_version . '</info>');

    // PHP version
    $output->writeln('PHP <info>'. PHP_VERSION . '</info>');

    // @todo check for other dependencies
    // @todo show as table "dependency : version : status"

  }
}
