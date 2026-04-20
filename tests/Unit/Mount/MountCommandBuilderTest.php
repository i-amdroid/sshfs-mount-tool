<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Unit\Mount;

use PHPUnit\Framework\TestCase;
use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Connection\Connection;
use SSHFSMountTool\Mount\MountCommandBuilder;
use SSHFSMountTool\Os\Os;
use SSHFSMountTool\Tests\Support\PreferencesFactory;
use SSHFSMountTool\Util\PathExpander;

final class MountCommandBuilderTest extends TestCase {

  public function testBuildsArgvWithKeyAuth(): void {
    $preferences = PreferencesFactory::create(['os' => Os::Linux, 'home' => '/home/iam']);
    $builder = $this->builder($preferences);
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      port: 2222,
      user: 'iam',
      key: '~/.ssh/id_rsa',
      mount: '~/mnt/msrv',
      remote: '/var/www',
      options: ['allow_other'],
    );

    $command = $builder->buildMount($connection);

    self::assertSame(
      [
        'sshfs',
        'iam@server.com:/var/www',
        '/home/iam/mnt/msrv',
        '-o',
        'follow_symlinks,StrictHostKeyChecking=accept-new,reconnect,ServerAliveInterval=15,ServerAliveCountMax=3,allow_other,IdentityFile=/home/iam/.ssh/id_rsa',
        '-p',
        '2222',
      ],
      $command->argv,
    );
    self::assertNull($command->stdin);
  }

  public function testPrependsVolnameOnMacOs(): void {
    $builder = $this->builder(PreferencesFactory::create(['os' => Os::Darwin]));
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      mount: '/Volumes/msrv',
    );

    $command = $builder->buildMount($connection);
    self::assertSame('-o', $command->argv[3]);
    self::assertStringStartsWith('volname=msrv,', $command->argv[4]);
  }

  public function testPasswordFlowsThroughStdin(): void {
    $builder = $this->builder(PreferencesFactory::create());
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      password: 's3cret',
      mount: '/mnt/msrv',
    );

    $command = $builder->buildMount($connection);
    self::assertSame('s3cret', $command->stdin);
    self::assertSame('-o', $command->argv[3]);
    self::assertStringContainsString('password_stdin', $command->argv[4]);
    // The password must never appear on the command line.
    foreach ($command->argv as $arg) {
      self::assertStringNotContainsString('s3cret', $arg);
    }
  }

  public function testUnmountUsesArgvFormAndExpandsPath(): void {
    $builder = $this->builder(PreferencesFactory::create([
      'home' => '/home/iam',
      'unmount' => ['fusermount', '-u'],
    ]));
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      mount: '~/mnt/msrv',
    );

    self::assertSame(
      ['fusermount', '-u', '/home/iam/mnt/msrv'],
      $builder->buildUnmount($connection),
    );
  }

  public function testDisplayMountMasksPassword(): void {
    $builder = $this->builder(PreferencesFactory::create());
    $connection = new Connection(
      id: 'msrv',
      title: 'My server',
      server: 'server.com',
      password: 's3cret',
      mount: '/mnt/msrv',
    );

    $command = $builder->buildMount($connection);
    $display = $builder->displayMount($command);
    self::assertStringStartsWith('echo [password] |', $display);
    self::assertStringNotContainsString('s3cret', $display);
  }

  public function testBuildMountThrowsWhenNoMountPoint(): void {
    $builder = $this->builder(PreferencesFactory::create());
    $connection = new Connection(id: 'x', title: 'x', server: 'x');

    $this->expectException(\RuntimeException::class);
    $builder->buildMount($connection);
  }

  private function builder(Preferences $preferences): MountCommandBuilder {
    return new MountCommandBuilder($preferences, new PathExpander($preferences->homePath));
  }

}
