<?php

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

    // Server, required
    $server = new Question('Server: ');
    $server->setValidator(function ($answer) {
      if (trim($answer) == '') {
        throw new \Exception('Server is required');
      }
      return $answer;
    });
    $connection_settings['server'] = $helper->ask($input, $output, $server);

    // Port
    $prompt_port = ($output->isVerbose()) ? 'Port. 22 is a default port, don\'t need to specify it []: ' : 'Port []: ';
    $port = new Question($prompt_port);
    $port->setValidator(function ($answer) {
      if ($answer == '') {
        return;
      }
      if (filter_var($answer, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 65535))) === false) {
        throw new \Exception('Invalid port value');
      }
      return $answer;
    });
    $connection_settings['port'] = $helper->ask($input, $output, $port);

    // User
    $user = new Question('Username []: ');
    $connection_settings['user'] = $helper->ask($input, $output, $user);

    // Password
    $prompt_password = ($output->isVerbose()) ? 'Password. If password not provided, it will be asked every time on connect. Leave blank for key auth []: ' : 'Password []: ';
    $password = new Question($prompt_password);
    $password->setHidden(TRUE);
    $connection_settings['password'] = $helper->ask($input, $output, $password);

    // Key
    $prompt_key = ($output->isVerbose()) ? 'Path to key file. Skip for using password auth [<comment>~/.ssh/id_rsa</comment>, n to skip]: ' : 'Key file. [<comment>~/.ssh/id_rsa</comment>, n to skip]: ';
    $key = new Question($prompt_key, '~/.ssh/id_rsa');
    $key->setValidator(function ($answer) {
      if ($answer == 'n' || $answer == 'N' || $answer == 'no' || $answer == 'No' || $answer == 'NO') {
        return;
      }
      return $answer;
    });
    $connection_settings['key'] = $helper->ask($input, $output, $key);

    // try to suggest default connection tile
    if (ip2long($connection_settings['server']) !== FALSE) {
      $default_title = str_replace('.', '-', $connection_settings['server']);
    }
    else {
      // 'server' is domain name
      $domain = explode('.', $connection_settings['server']);
      if (count($domain) > 1) {
        // domain without zone
        $default_title = $domain[count($domain) - 2];
      }
      else {
        // complex domain name, so just print full domain
        $default_title = $connection_settings['server'];
      }
    }

    // Mount
    $default_mount = $preferences['mount_path'] . '/' . $default_title;
    $prompt_mount = ($output->isVerbose()) ? 'Mount directory. Required for mounting [<comment>' . $default_mount . '</comment>]: ' : 'Mount directory [<comment>' . $default_mount . '</comment>]: ';
    $mount = new Question($prompt_mount, $default_mount);
    $connection_settings['mount'] = $helper->ask($input, $output, $mount);

    // Remote
    $remote = new Question('Remote directory []: ');
    $connection_settings['remote'] = $helper->ask($input, $output, $remote);

    // Options
    $prompt_options = ($output->isVerbose()) ? 'Mount options, separated by comma []: ' : 'Mount options []: ';
    $options_question = new Question($prompt_options);
    $options = $helper->ask($input, $output, $options_question);
    $options = explode(',', $options ? $options : '');
    $options = array_map('trim', $options);
    $connection_settings['options'] = array_filter($options);

    // Title
    $title_question = new Question('Connection title [<comment>' . $default_title . '</comment>]: ', $default_title);
    $title = $helper->ask($input, $output, $title_question);
    $connection_settings = ['title' => $title] + $connection_settings;

    // try to suggest default connection id
    $default_cid = preg_replace('#[aeiouy\-_\s]+#i', '', substr($connection_settings['title'], 1));
    $default_cid = strtolower(substr($connection_settings['title'], 0, 1) . $default_cid);
    $default_cid = substr($default_cid, 0, 3);

    // Connection ID
    $prompt_cid = ($output->isVerbose()) ? 'Connection ID. Used as shortcut, must be unique [<comment>' . $default_cid . '</comment>]: ' : 'Connection ID [<comment>' . $default_cid . '</comment>]: ';
    $cid_question = new Question($prompt_cid, $default_cid);
    $cid = $helper->ask($input, $output, $cid_question);

    // Show settings
    if ($output->isVerbose()) {
      $output->writeln('');
      $table = gen_connection_settings_table($cid, $connection_settings, $output);
      $table->render();
      $output->writeln('');
    }

    // Saving
    $prompt_save = ($output->isVerbose()) ? 'Save config [<comment>y — globally</comment> / l — locally (current directory) / n, c to cancel]? ' : 'Save config [<comment>y — globally</comment> / l — locally / n, c to cancel]? ';
    $save_question = new Question($prompt_save);
    $save_question->setValidator(function ($answer) {
      if ($answer == '' || $answer == 'g' || $answer == 'G' || $answer == 'globally' || $answer == 'Globally' || $answer == 'GLOBALLY' ||
        $answer == 'y' || $answer == 'Y' || $answer == 'yes' || $answer == 'Yes' || $answer == 'YES') {
        // return from callback without $cid
        return 'global';
      }
      elseif ($answer == 'l' || $answer == 'L' || $answer == 'locally' || $answer == 'Locally' || $answer == 'LOCALLY') {
        // return from callback without $cid
        return 'local';
      }
      elseif ($answer == 'c' || $answer == 'C' || $answer == 'cancel' || $answer == 'Cancel' || $answer == 'CANCEL' ||
        $answer == 'n' || $answer == 'N' || $answer == 'no' || $answer == 'No' || $answer == 'NO') {
        // return from callback without value
        return;
      }
      else {
        throw new \RuntimeException(
          $answer . ' is not a valid answer.'
        );
      }
    });

    $save = $helper->ask($input, $output, $save_question);

    if ($save == 'global') {
      $preferences['global'] = TRUE;
      set_connection_settings($cid, $connection_settings, $input, $output, $helper);
    }
    elseif ($save == 'local') {
      $preferences['global'] = FALSE;
      $local = TRUE;
      set_connection_settings($cid, $connection_settings, $input, $output, $helper, $local);
    }
    else {
      // canceled
      return Command::SUCCESS;
    }

    // here can be only success savings
    $output->writeln('<info>Connection saved</info>');

    return Command::SUCCESS;

  }
}
