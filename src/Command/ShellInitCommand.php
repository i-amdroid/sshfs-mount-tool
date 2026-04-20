<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints a shell wrapper that turns `smt cd <id>` into an in-place directory
 * change in the user's current tab.
 *
 * A child process cannot change its parent shell's working directory, so we
 * emit a function that calls `smt cd --eval` (which prints `cd '/path'`) and
 * evaluates the result in the parent shell.
 *
 * Usage:
 *   eval "$(smt shell-init bash)"    # bash/zsh in ~/.bashrc or ~/.zshrc
 *   smt shell-init fish | source     # fish in ~/.config/fish/config.fish
 */
#[AsCommand(
  name: 'shell-init',
  description: 'Print a shell wrapper enabling in-place `smt cd`',
)]
final class ShellInitCommand extends Command {

  /** @var list<string> */
  private const array SUPPORTED_SHELLS = ['bash', 'zsh', 'fish'];

  protected function configure(): void {
    $this
      ->addArgument('shell', InputArgument::REQUIRED, 'Target shell: bash, zsh, or fish')
      ->setHelp(<<<'EOF'
        Prints a shell function that makes `smt cd <id>` change the current tab's
        directory. Add the following line to your shell rc file:

          <info>eval "$(smt shell-init bash)"</info>   # or zsh
          <info>smt shell-init fish | source</info>    # fish

        After that, `smt cd msrv` works in-place. All other subcommands are
        forwarded to smt unchanged.
        EOF);
  }

  public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void {
    if ($input->mustSuggestArgumentValuesFor('shell')) {
      $suggestions->suggestValues(self::SUPPORTED_SHELLS);
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $shell = strtolower((string) $input->getArgument('shell'));

    $script = match ($shell) {
      'bash', 'zsh' => self::POSIX_WRAPPER,
      'fish' => self::FISH_WRAPPER,
      default => NULL,
    };

    if ($script === NULL) {
      $output->writeln(sprintf(
        '<error>Unsupported shell "%s". Supported: %s</error>',
        $shell,
        implode(', ', self::SUPPORTED_SHELLS),
      ));
      return Command::INVALID;
    }

    $output->write($script);
    return Command::SUCCESS;
  }

  private const string POSIX_WRAPPER = <<<'EOF'
    # smt shell integration — makes `smt cd <id>` change the current tab's dir.
    smt() {
      if [ "$1" = "cd" ]; then
        shift
        local __smt_cd
        __smt_cd=$(command smt cd --eval "$@") || return $?
        [ -n "$__smt_cd" ] && eval "$__smt_cd"
      else
        command smt "$@"
      fi
    }

    EOF;

  private const string FISH_WRAPPER = <<<'EOF'
    # smt shell integration — makes `smt cd <id>` change the current tab's dir.
    function smt
      if test "$argv[1]" = "cd"
        set -l __smt_cd (command smt cd --eval $argv[2..-1])
        or return $status
        test -n "$__smt_cd"; and eval "$__smt_cd"
      else
        command smt $argv
      end
    end

    EOF;

}
