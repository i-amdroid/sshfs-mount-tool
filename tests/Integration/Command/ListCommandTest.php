<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class ListCommandTest extends IntegrationTestCase {

  public function testListsSingleConnectionAutomatically(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'myserver',
        'server' => 'server.com',
        'user' => 'iam',
        'mount' => '~/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('list');
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    $display = $tester->getDisplay();
    self::assertStringContainsString('msrv', $display);
    self::assertStringContainsString('myserver', $display);
    self::assertStringContainsString('server.com', $display);
  }

  public function testReportsNoSavedConnections(): void {
    $tester = $this->tester('list');
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('No saved connections', $tester->getDisplay());
  }

  public function testMasksPasswordInOutput(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'password' => 's3cret',
        'mount' => '~/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('list');
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    $display = $tester->getDisplay();
    self::assertStringContainsString('[password]', $display);
    self::assertStringNotContainsString('s3cret', $display);
  }

}
