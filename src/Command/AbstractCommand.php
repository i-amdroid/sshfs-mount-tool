<?php

declare(strict_types=1);

namespace SSHFSMountTool\Command;

use SSHFSMountTool\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractCommand extends Command {

  public function __construct(
    protected readonly Services $services,
  ) {
    parent::__construct();
  }

  public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void {
    if ($input->mustSuggestArgumentValuesFor('connection_id')) {
      $suggestions->suggestValues(array_keys($this->services->connections->all()));
    }
  }

  /**
   * Pull a string argument — `InputInterface::getArgument` returns `mixed`
   * and Symfony offers no typed variant, so every call site needs this cast.
   */
  protected function stringArgument(InputInterface $input, string $name): ?string {
    $value = $input->getArgument($name);
    return is_string($value) && $value !== '' ? $value : NULL;
  }

  protected function stringOption(InputInterface $input, string $name): ?string {
    $value = $input->getOption($name);
    return is_string($value) && $value !== '' ? $value : NULL;
  }

  protected function boolOption(InputInterface $input, string $name): bool {
    return $input->getOption($name) === TRUE;
  }

}
