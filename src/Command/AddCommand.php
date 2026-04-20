<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use SSHFSMountTool\Connection\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'add',
  description: 'Add connection',
)]
final class AddCommand extends AbstractCommand {

  private const string SAVE_GLOBAL = 'global';
  private const string SAVE_LOCAL = 'local';
  private const string SAVE_CANCEL = 'cancel';

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $verbose = $output->isVerbose();

    $server = $this->askRequired($io, 'Server');
    $port = $this->askPort($io, $verbose);
    $user = $this->askOptional($io, 'Username');

    $password_raw = $io->askHidden(
      $verbose
        ? 'Password (leave blank for key auth — if not set, it will be asked every time on connect)'
        : 'Password',
    );
    $password = is_string($password_raw) && $password_raw !== '' ? $password_raw : NULL;

    $key_prompt = $verbose
      ? 'Path to key file (type "n" to skip and use password auth)'
      : 'Key file (type "n" to skip)';
    $key_question = new Question($key_prompt, '~/.ssh/id_rsa');
    $key_question->setValidator(static function (?string $answer): ?string {
      if ($answer === NULL) {
        return NULL;
      }
      return in_array(strtolower($answer), ['n', 'no'], TRUE) ? NULL : $answer;
    });
    $key_raw = $io->askQuestion($key_question);
    $key = is_string($key_raw) && $key_raw !== '' ? $key_raw : NULL;

    $default_title = $this->suggestTitle($server);

    $default_mount = rtrim($this->services->preferences->mountPath, '/') . '/' . $default_title;
    $mount_prompt = $verbose
      ? 'Mount directory (required for mounting)'
      : 'Mount directory';
    $mount = (string) $io->askQuestion(new Question($mount_prompt, $default_mount));

    $remote = $this->askOptional($io, 'Remote directory');

    $options_prompt = $verbose
      ? 'Mount options (comma-separated)'
      : 'Mount options';
    $options_raw = $io->askQuestion(new Question($options_prompt));
    $options = $this->parseCommaList((string) ($options_raw ?? ''));

    $title = (string) $io->askQuestion(new Question('Connection title', $default_title));

    $default_cid = $this->suggestCid($title);
    $cid_prompt = $verbose
      ? 'Connection ID (used as shortcut, must be unique)'
      : 'Connection ID';
    $cid = (string) $io->askQuestion(new Question($cid_prompt, $default_cid));

    $connection = new Connection(
      id: $cid,
      title: $title,
      server: $server,
      port: $port,
      user: $user,
      password: $password,
      key: $key,
      mount: $mount,
      remote: $remote,
      options: $options,
    );

    if ($verbose) {
      $io->newLine();
      $this->services->settingsTableRenderer->render($io, $connection);
      $io->newLine();
    }

    $scope = $this->askSaveScope($io);
    if ($scope === self::SAVE_CANCEL) {
      return Command::SUCCESS;
    }

    $use_current = $scope === self::SAVE_LOCAL;
    if ($this->services->connections->exists($cid) && !$this->confirmOverwrite($io, $cid)) {
      return Command::SUCCESS;
    }
    $this->services->connections->save($connection, useCurrent: $use_current);

    $io->writeln('<info>Connection saved</info>');
    return Command::SUCCESS;
  }

  private function askRequired(SymfonyStyle $io, string $prompt): string {
    $question = new Question($prompt);
    $question->setValidator(static function (?string $answer): string {
      if ($answer === NULL || trim($answer) === '') {
        throw new \RuntimeException('Server is required');
      }
      return $answer;
    });
    return (string) $io->askQuestion($question);
  }

  private function askOptional(SymfonyStyle $io, string $prompt): ?string {
    $answer = $io->askQuestion(new Question($prompt));
    return is_string($answer) && $answer !== '' ? $answer : NULL;
  }

  private function askPort(SymfonyStyle $io, bool $verbose): ?int {
    $prompt = $verbose
      ? 'Port (leave empty for the default 22)'
      : 'Port';
    $question = new Question($prompt);
    $question->setValidator(static function (?string $answer): ?int {
      if ($answer === NULL || trim($answer) === '') {
        return NULL;
      }
      $int = filter_var($answer, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 65_535],
      ]);
      if ($int === FALSE) {
        throw new \RuntimeException('Invalid port value');
      }
      return $int;
    });
    $value = $io->askQuestion($question);
    return is_int($value) ? $value : NULL;
  }

  private function askSaveScope(SymfonyStyle $io): string {
    $question = new ChoiceQuestion(
      'Save config',
      [
        'y' => 'globally (user config)',
        'l' => 'locally (./smt.yml)',
        'n' => 'cancel',
      ],
      'y',
    );
    $question->setErrorMessage('%s is not a valid answer.');

    $answer = $io->askQuestion($question);
    return match (strtolower((string) $answer)) {
      'y', 'yes', 'g', 'globally', 'globally (user config)' => self::SAVE_GLOBAL,
      'l', 'locally', 'locally (./smt.yml)' => self::SAVE_LOCAL,
      default => self::SAVE_CANCEL,
    };
  }

  private function confirmOverwrite(SymfonyStyle $io, string $cid): bool {
    $answer = $io->askQuestion(new ConfirmationQuestion(
      sprintf('Connection "%s" already exists, overwrite it?', $cid),
      FALSE,
    ));
    return $answer === TRUE;
  }

  private function suggestTitle(string $server): string {
    if (ip2long($server) !== FALSE) {
      return str_replace('.', '-', $server);
    }
    $parts = explode('.', $server);
    if (count($parts) > 1) {
      return $parts[count($parts) - 2];
    }
    return $server;
  }

  private function suggestCid(string $title): string {
    $stripped = preg_replace('#[aeiouy\-_\s]+#i', '', substr($title, 1)) ?? '';
    $cid = strtolower(substr($title, 0, 1) . $stripped);
    return substr($cid, 0, 3);
  }

  /**
   * @return list<string>
   */
  private function parseCommaList(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
      return [];
    }
    $items = array_map('trim', explode(',', $raw));
    return array_values(array_filter($items, static fn (string $v): bool => $v !== ''));
  }

}
