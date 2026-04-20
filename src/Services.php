<?php

declare(strict_types=1);

namespace SSHFSMountTool;

use SSHFSMountTool\Config\ConnectionRepository;
use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Config\PreferencesLoader;
use SSHFSMountTool\Connection\ConnectionResolver;
use SSHFSMountTool\Mount\MountCommandBuilder;
use SSHFSMountTool\Mount\MountInspector;
use SSHFSMountTool\Mount\MountService;
use SSHFSMountTool\Process\ProcessRunner;
use SSHFSMountTool\Process\SymfonyProcessRunner;
use SSHFSMountTool\Ssh\SshCommandBuilder;
use SSHFSMountTool\Ssh\SshSessionLauncher;
use SSHFSMountTool\Table\ConnectionSettingsTableRenderer;
use SSHFSMountTool\Table\ConnectionTableRenderer;
use SSHFSMountTool\Terminal\TerminalLauncher;
use SSHFSMountTool\Util\PathExpander;

/**
 * Lightweight service wiring — not a DI container, just a struct that
 * constructs the object graph once at app boot.
 */
final class Services {

  public readonly Preferences $preferences;
  public readonly PathExpander $pathExpander;
  public readonly ProcessRunner $processRunner;
  public readonly ConnectionRepository $connections;
  public readonly MountCommandBuilder $mountCommandBuilder;
  public readonly MountInspector $mountInspector;
  public readonly MountService $mountService;
  public readonly SshCommandBuilder $sshCommandBuilder;
  public readonly TerminalLauncher $terminalLauncher;
  public readonly SshSessionLauncher $sshSessionLauncher;
  public readonly ConnectionTableRenderer $tableRenderer;
  public readonly ConnectionSettingsTableRenderer $settingsTableRenderer;
  public readonly ConnectionResolver $resolver;

  public function __construct(
    ?Preferences $preferences = NULL,
    ?ProcessRunner $processRunner = NULL,
  ) {
    $this->preferences = $preferences ?? new PreferencesLoader()->load();
    $this->pathExpander = new PathExpander($this->preferences->homePath);
    $this->processRunner = $processRunner ?? new SymfonyProcessRunner();
    $this->connections = new ConnectionRepository($this->preferences);
    $this->mountCommandBuilder = new MountCommandBuilder($this->preferences, $this->pathExpander);
    $this->mountInspector = new MountInspector(
      $this->preferences,
      $this->connections,
      $this->processRunner,
      $this->pathExpander,
    );
    $this->mountService = new MountService($this->mountCommandBuilder, $this->processRunner, $this->pathExpander);
    $this->sshCommandBuilder = new SshCommandBuilder($this->preferences);
    $this->terminalLauncher = new TerminalLauncher($this->preferences, $this->processRunner);
    $this->sshSessionLauncher = new SshSessionLauncher(
      $this->sshCommandBuilder,
      $this->processRunner,
      $this->terminalLauncher,
    );
    $this->tableRenderer = new ConnectionTableRenderer();
    $this->settingsTableRenderer = new ConnectionSettingsTableRenderer();
    $this->resolver = new ConnectionResolver($this->connections, $this->mountInspector, $this->tableRenderer);
  }

}
