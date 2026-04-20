<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class AddCommandTest extends IntegrationTestCase {

  public function testSavesNewConnectionInteractively(): void {
    $tester = $this->tester('add');
    $tester->setInputs([
      'server.com', // Server
      '', // Port (default)
      'iam', // User
      '', // Password (hidden)
      'n', // Key file (skip)
      '', // Mount directory (accept default)
      '/var/www', // Remote directory
      '', // Options
      'myserver', // Title
      'msrv', // Connection ID
      'y', // Save scope (globally)
    ]);

    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('Connection saved', $tester->getDisplay());

    $saved = $this->readConnections($this->preferences()->userConfigFile);
    self::assertArrayHasKey('msrv', $saved);
    self::assertSame('server.com', $saved['msrv']['server']);
    self::assertSame('iam', $saved['msrv']['user']);
    self::assertSame('myserver', $saved['msrv']['title']);
    self::assertSame('/var/www', $saved['msrv']['remote']);
  }

  public function testSavesLocallyWhenChosen(): void {
    $tester = $this->tester('add');
    $tester->setInputs([
      'server.com',
      '',
      'iam',
      '',
      'n',
      '',
      '',
      '',
      'myserver',
      'msrv',
      'l', // save locally
    ]);
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    $saved = $this->readConnections($this->preferences()->currentConfigFile);
    self::assertArrayHasKey('msrv', $saved);
  }

  public function testCancelsWithoutSaving(): void {
    $tester = $this->tester('add');
    $tester->setInputs([
      'server.com',
      '',
      'iam',
      '',
      'n',
      '',
      '',
      '',
      'myserver',
      'msrv',
      'n', // cancel
    ]);
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    self::assertStringNotContainsString('Connection saved', $tester->getDisplay());
    self::assertFileDoesNotExist($this->preferences()->userConfigFile);
  }

}
