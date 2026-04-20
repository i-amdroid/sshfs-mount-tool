<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class CdCommandTest extends IntegrationTestCase {

  public function testEvalModePrintsQuotedCdCommand(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => $this->home . '/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('cd');
    $tester->execute(['connection_id' => 'msrv', '--eval' => TRUE]);

    self::assertCommandSucceeds($tester);
    $display = $tester->getDisplay();
    self::assertStringContainsString(
      'cd ' . escapeshellarg($this->home . '/mnt/msrv'),
      $display,
    );
    // In eval mode no terminal should be launched.
    foreach ($this->runner->calls as $call) {
      self::assertNotSame('shell', $call->type);
    }
  }

  public function testEvalModeExpandsTilde(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => '~/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('cd');
    $tester->execute(['connection_id' => 'msrv', '--eval' => TRUE]);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString(
      'cd ' . escapeshellarg($this->home . '/mnt/msrv'),
      $tester->getDisplay(),
    );
  }

  public function testDefaultModeSpawnsTerminalTab(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => '/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('cd');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    $shell_calls = array_filter(
      $this->runner->calls,
      static fn ($call): bool => $call->type === 'shell',
    );
    self::assertNotEmpty($shell_calls);
  }

}
