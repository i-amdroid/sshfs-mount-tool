<?php

declare(strict_types=1);

namespace SSHFSMountTool\Config;

use SSHFSMountTool\Os\Os;
use SSHFSMountTool\Terminal\Terminal;

/**
 * Immutable, fully-merged runtime preferences.
 *
 * Built by {@see PreferencesLoader} from defaults, OS-specific values, and
 * the user's optional config file. Passed by value into services that need it.
 */
final class Preferences {

  /**
   * @param list<string>          $defaultOptions
   * @param list<string>          $defaultSshOptions
   * @param list<string>          $unmountCmd       argv form
   * @param array<string, Terminal> $terminals
   */
  public function __construct(
    public readonly Os $os,
    public readonly string $homePath,
    public readonly string $currentPath,
    public readonly string $userConfigFile,
    public readonly string $currentConfigFile,
    public readonly string $userPreferencesFile,
    public readonly string $mountPath,
    public readonly string $mountCmd,
    public readonly string $mountsListCmd,
    public readonly string $mountsListType,
    public readonly array $unmountCmd,
    public readonly string $editor,
    public readonly string $terminalName,
    public readonly array $terminals,
    public readonly array $defaultOptions,
    public readonly array $defaultSshOptions,
    public readonly bool $global = FALSE,
  ) {}

  public function withGlobal(bool $global): self {
    return new self(
      os: $this->os,
      homePath: $this->homePath,
      currentPath: $this->currentPath,
      userConfigFile: $this->userConfigFile,
      currentConfigFile: $this->currentConfigFile,
      userPreferencesFile: $this->userPreferencesFile,
      mountPath: $this->mountPath,
      mountCmd: $this->mountCmd,
      mountsListCmd: $this->mountsListCmd,
      mountsListType: $this->mountsListType,
      unmountCmd: $this->unmountCmd,
      editor: $this->editor,
      terminalName: $this->terminalName,
      terminals: $this->terminals,
      defaultOptions: $this->defaultOptions,
      defaultSshOptions: $this->defaultSshOptions,
      global: $global,
    );
  }

  /**
   * Resolve which connections file should be used for reads.
   *
   * Global option forces user file. Otherwise, a per-project file in the
   * current directory wins if it exists.
   */
  public function activeConfigFile(): string {
    if ($this->global) {
      return $this->userConfigFile;
    }
    if (file_exists($this->currentConfigFile)) {
      return $this->currentConfigFile;
    }
    return $this->userConfigFile;
  }

  public function terminal(): ?Terminal {
    return $this->terminals[$this->terminalName] ?? NULL;
  }

}
