<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class SshCommandTest extends IntegrationTestCase {

  public function testSameTabIsDefaultAndAttachesToTty(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'user' => 'iam',
        'mount' => '/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('ssh');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    self::assertNotEmpty($this->runner->calls);
    $call = $this->runner->calls[count($this->runner->calls) - 1];
    self::assertSame('tty', $call->type);
    self::assertContains('iam@server.com', $call->argv);
    self::assertSame('ssh', $call->argv[0]);
  }

  public function testNewTabOptionSpawnsTerminal(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'user' => 'iam',
        'mount' => '/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('ssh');
    $tester->execute(['connection_id' => 'msrv', '--new-tab' => TRUE]);

    self::assertCommandSucceeds($tester);
    $shell_calls = array_filter(
      $this->runner->calls,
      static fn ($call): bool => $call->type === 'shell',
    );
    self::assertNotEmpty($shell_calls);
  }

  public function testSameTabWithPasswordWrapsInSshpass(): void {
    $this->seedConnections([
      'msrv' => [
        'title' => 'msrv',
        'server' => 'server.com',
        'user' => 'iam',
        'password' => 's3cret',
        'mount' => '/mnt/msrv',
      ],
    ]);

    $tester = $this->tester('ssh');
    $tester->execute(['connection_id' => 'msrv']);

    self::assertCommandSucceeds($tester);
    $call = $this->runner->calls[count($this->runner->calls) - 1];
    self::assertSame('tty', $call->type);
    self::assertSame('sshpass', $call->argv[0]);
    self::assertSame('-f', $call->argv[1]);
    // Password arrives via tempfile, never on the command line.
    foreach ($call->argv as $arg) {
      self::assertStringNotContainsString('s3cret', $arg);
    }
  }

}
