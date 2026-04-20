<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SSHFSMountTool\Application;
use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Services;
use SSHFSMountTool\Tests\Support\FakeProcessRunner;
use SSHFSMountTool\Tests\Support\PreferencesFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

abstract class IntegrationTestCase extends TestCase {

  protected string $home;

  protected string $currentDir;

  protected FakeProcessRunner $runner;

  protected Services $services;

  protected Application $application;

  protected function setUp(): void {
    $root = sys_get_temp_dir() . '/smt-test-' . bin2hex(random_bytes(4));
    $this->home = $root . '/home';
    $this->currentDir = $root . '/work';
    mkdir($this->home, 0o777, TRUE);
    mkdir($this->currentDir, 0o777, TRUE);

    $preferences = PreferencesFactory::create([
      'home' => $this->home,
      'current' => $this->currentDir,
      'global' => TRUE,
    ]);

    $this->runner = new FakeProcessRunner();
    $this->services = new Services($preferences, $this->runner);
    $this->application = new Application($this->services);
  }

  protected function tearDown(): void {
    $root = dirname($this->home);
    if (is_dir($root)) {
      self::rmrf($root);
    }
  }

  private static function rmrf(string $path): void {
    if (is_link($path) || !is_dir($path)) {
      @unlink($path);
      return;
    }
    foreach ((array) scandir($path) as $entry) {
      if ($entry === '.' || $entry === '..' || !is_string($entry)) {
        continue;
      }
      self::rmrf($path . DIRECTORY_SEPARATOR . $entry);
    }
    @rmdir($path);
  }

  protected function tester(string $name): CommandTester {
    $command = $this->application->find($name);
    return new CommandTester($command);
  }

  /**
   * @param array<string, mixed> $connections
   */
  protected function seedConnections(array $connections): void {
    $dir = $this->home . '/.config/smt';
    if (!is_dir($dir)) {
      mkdir($dir, 0o700, TRUE);
    }
    file_put_contents($dir . '/smt.yml', Yaml::dump(['connections' => $connections], 4, 2));
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  protected function readConnections(string $file): array {
    if (!file_exists($file)) {
      return [];
    }
    $data = Yaml::parseFile($file);
    $connections = is_array($data) ? $data['connections'] ?? NULL : NULL;
    if (!is_array($connections)) {
      return [];
    }
    $out = [];
    foreach ($connections as $id => $settings) {
      if (!is_array($settings)) {
        continue;
      }

      /** @var array<string, mixed> $settings */
      $out[(string) $id] = $settings;
    }
    return $out;
  }

  protected function preferences(): Preferences {
    return $this->services->preferences;
  }

  protected static function assertCommandSucceeds(CommandTester $tester): void {
    self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
  }

}
