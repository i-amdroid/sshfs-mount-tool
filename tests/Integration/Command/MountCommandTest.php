<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class MountCommandTest extends IntegrationTestCase {

  public function testMountsSingleConnectionAutomatically(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'user' => 'iam',
        'mount' => $this->home . '/mnt/msrv',
        'remote' => '/var/www',
      ],
    ]);
    $this->runner->queueRun(new ProcessResult(0, '', ''));

    $tester = $this->tester('mount');
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('mounted', $tester->getDisplay());
    self::assertDirectoryExists($this->home . '/mnt/msrv');

    $call = $this->runner->calls[0];
    self::assertSame('run', $call->type);
    self::assertContains('iam@server.com:/var/www', $call->argv);
  }

  public function testPasswordFlowsThroughStdin(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => $this->home . '/mnt/msrv',
      ],
    ]);
    $this->runner->queueRun(new ProcessResult(0, '', ''));

    $tester = $this->tester('mount');
    $tester->execute(['--password' => 's3cret']);

    self::assertCommandSucceeds($tester);
    $call = $this->runner->calls[0];
    self::assertSame('s3cret', $call->input);
    foreach ($call->argv as $arg) {
      self::assertStringNotContainsString('s3cret', $arg);
    }
  }

  public function testExpandsTildeInMountPath(): void {
    // Configured mount path is tilde-relative; sshfs (run via argv, no shell)
    // must receive the absolute path.
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => '~/mnt/msrv',
      ],
    ]);
    $this->runner->queueRun(new ProcessResult(0, '', ''));

    $tester = $this->tester('mount');
    $tester->execute([]);

    self::assertCommandSucceeds($tester);
    $call = $this->runner->calls[0];
    self::assertContains($this->home . '/mnt/msrv', $call->argv);
    foreach ($call->argv as $arg) {
      self::assertStringStartsNotWith('~', $arg);
    }
  }

  public function testFailsOnSshfsError(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'mount' => $this->home . '/mnt/msrv',
      ],
    ]);
    $this->runner->queueRun(new ProcessResult(1, '', 'mount_macfuse: mount point does not exist'));

    $tester = $this->tester('mount');
    $tester->execute([]);

    self::assertSame(1, $tester->getStatusCode());
    self::assertStringContainsString('mount point does not exist', $tester->getDisplay());
  }

}
