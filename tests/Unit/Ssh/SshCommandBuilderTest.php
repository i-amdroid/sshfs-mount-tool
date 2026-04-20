<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Unit\Ssh;

use PHPUnit\Framework\TestCase;
use SSHFSMountTool\Connection\Connection;
use SSHFSMountTool\Ssh\SshCommandBuilder;
use SSHFSMountTool\Tests\Support\PreferencesFactory;

final class SshCommandBuilderTest extends TestCase {

  public function testBuildsArgvWithDefaultAndCustomSshOptions(): void {
    $builder = new SshCommandBuilder(PreferencesFactory::create());
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      port: 2222,
      user: 'iam',
      sshOptions: ['-o StrictHostKeyChecking=no'],
    );

    self::assertSame(
      [
        'ssh',
        '-o',
        'ServerAliveInterval=60',
        '-o',
        'ServerAliveCountMax=3',
        '-o',
        'StrictHostKeyChecking=no',
        'iam@server.com',
        '-p',
        '2222',
      ],
      $builder->buildArgv($connection),
    );
  }

  public function testOmitsUserWhenNotSet(): void {
    $builder = new SshCommandBuilder(PreferencesFactory::create());
    $connection = new Connection(id: 'srv', title: 'srv', server: 'server.com');

    self::assertSame(
      ['ssh', '-o', 'ServerAliveInterval=60', '-o', 'ServerAliveCountMax=3', 'server.com'],
      $builder->buildArgv($connection),
    );
  }

  public function testArgvToShellQuotesEveryToken(): void {
    $argv = ['ssh', '-o', 'ProxyCommand=nc -x 127.0.0.1:1080 %h %p', 'iam@host'];
    $rendered = SshCommandBuilder::argvToShell($argv);
    self::assertStringContainsString("'ProxyCommand=nc -x 127.0.0.1:1080 %h %p'", $rendered);
    self::assertStringStartsWith("'ssh'", $rendered);
  }

}
