<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class RemoveCommandTest extends IntegrationTestCase {

  public function testRemovesConnection(): void {
    $this->seedConnections([
      'msrv' => ['title' => 'msrv', 'server' => 'server.com', 'mount' => '/mnt/msrv'],
      'other' => ['title' => 'other', 'server' => 'other.com', 'mount' => '/mnt/other'],
    ]);

    $tester = $this->tester('remove');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('Connection removed', $tester->getDisplay());

    $saved = $this->readConnections($this->preferences()->userConfigFile);
    self::assertArrayNotHasKey('msrv', $saved);
    self::assertArrayHasKey('other', $saved);
  }

  public function testRejectsUnknownConnection(): void {
    $this->seedConnections([
      'msrv' => ['title' => 'msrv', 'server' => 'server.com', 'mount' => '/mnt/msrv'],
    ]);

    $tester = $this->tester('remove');
    $tester->execute(['connection_id' => 'nope']);

    self::assertStringContainsString('not a valid connection ID', $tester->getDisplay());
  }

}
