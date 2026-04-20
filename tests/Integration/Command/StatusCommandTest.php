<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class StatusCommandTest extends IntegrationTestCase {

  public function testReportsNotMountedWhenMountOutputIsEmpty(): void {
    $this->seedConnections([
      'msrv' => ['title' => 'msrv', 'server' => 'server.com', 'mount' => '/mnt/msrv'],
    ]);
    $this->runner->queueRun(new ProcessResult(0, '', ''));

    $tester = $this->tester('status');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('msrv is not mounted', $tester->getDisplay());
  }

  public function testReportsMountedWhenMountOutputMatches(): void {
    $this->seedConnections([
      'msrv' => ['title' => 'msrv', 'server' => 'server.com', 'mount' => '/mnt/msrv'],
    ]);
    $this->runner->queueRun(new ProcessResult(
      0,
      "user@server.com:/ on /mnt/msrv type fuse.sshfs (rw)\n",
      '',
    ));

    $tester = $this->tester('status');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('msrv is', $tester->getDisplay());
    self::assertStringContainsString('mounted', $tester->getDisplay());
  }

}
