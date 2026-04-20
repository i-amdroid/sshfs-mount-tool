<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Unit\Mount;

use PHPUnit\Framework\TestCase;
use SSHFSMountTool\Config\ConnectionRepository;
use SSHFSMountTool\Config\Preferences;
use SSHFSMountTool\Mount\MountInspector;
use SSHFSMountTool\Process\ProcessResult;
use SSHFSMountTool\Tests\Support\FakeProcessRunner;
use SSHFSMountTool\Tests\Support\PreferencesFactory;
use SSHFSMountTool\Util\PathExpander;
use Symfony\Component\Yaml\Yaml;

final class MountInspectorTest extends TestCase {

  public function testFindsMountedConnectionIdsViaPath(): void {
    $preferences = $this->preferencesWithConfig([
      'connections' => [
        'msrv' => [
          'title' => 'msrv',
          'server' => 'server.com',
          'mount' => '~/mnt/msrv',
        ],
        'other' => [
          'title' => 'other',
          'server' => 'other.com',
          'mount' => '/mnt/other',
        ],
      ],
    ]);

    $runner = new FakeProcessRunner();
    $runner->queueRun(
      new ProcessResult(
        0,
        $this->mountOutput([
          'user@server.com:/var/www on ' . $preferences->homePath . '/mnt/msrv type fuse.sshfs (rw,nosuid,nodev)',
          'user@other.com:/srv on /mnt/other type fuse.sshfs (rw)',
          'tmpfs on /tmp type tmpfs (rw)',
        ]),
        '',
      ),
    );

    $repo = new ConnectionRepository($preferences);
    $inspector = new MountInspector($preferences, $repo, $runner, new PathExpander($preferences->homePath));

    self::assertSame(['msrv', 'other'], $inspector->mountedIds());
  }

  public function testIgnoresNonMatchingMountLines(): void {
    $preferences = $this->preferencesWithConfig([
      'connections' => [
        'msrv' => [
          'title' => 'msrv',
          'server' => 'server.com',
          'mount' => '/mnt/msrv',
        ],
      ],
    ]);

    $runner = new FakeProcessRunner();
    $runner->queueRun(new ProcessResult(0, "tmpfs on /tmp type tmpfs (rw)\n", ''));

    $repo = new ConnectionRepository($preferences);
    $inspector = new MountInspector($preferences, $repo, $runner, new PathExpander($preferences->homePath));

    self::assertSame([], $inspector->mountedIds());
  }

  public function testParsesMacOsMountOutputWithSpacesInPath(): void {
    $preferences = $this->preferencesWithConfig([
      'connections' => [
        'msrv' => [
          'title' => 'msrv',
          'server' => 'server.com',
          'mount' => '/Users/iamdroid/mnt/Test server',
        ],
      ],
    ], 'macfuse');

    $runner = new FakeProcessRunner();
    $runner->queueRun(
      new ProcessResult(
        0,
        "user@server.com@0 on /Users/iamdroid/mnt/Test server (macfuse, nodev, nosuid)\n",
        '',
      ),
    );

    $repo = new ConnectionRepository($preferences);
    $inspector = new MountInspector($preferences, $repo, $runner, new PathExpander($preferences->homePath));

    self::assertSame(['msrv'], $inspector->mountedIds());
  }

  public function testParsesLinuxMountOutputWithOctalEscapedSpaces(): void {
    $preferences = $this->preferencesWithConfig([
      'connections' => [
        'msrv' => [
          'title' => 'msrv',
          'server' => 'server.com',
          'mount' => '/home/iam/mnt/Test server',
        ],
      ],
    ]);

    $runner = new FakeProcessRunner();
    $runner->queueRun(
      new ProcessResult(
        0,
        "user@server.com: on /home/iam/mnt/Test\\040server type fuse.sshfs (rw,nosuid,nodev)\n",
        '',
      ),
    );

    $repo = new ConnectionRepository($preferences);
    $inspector = new MountInspector($preferences, $repo, $runner, new PathExpander($preferences->homePath));

    self::assertSame(['msrv'], $inspector->mountedIds());
  }

  /**
   * @param array<string, mixed> $config
   */
  private function preferencesWithConfig(array $config, string $mountsListType = 'fuse.sshfs'): Preferences {
    $home = sys_get_temp_dir() . '/smt-test-' . bin2hex(random_bytes(4));
    @mkdir($home . '/.config/smt', 0o777, TRUE);
    file_put_contents($home . '/.config/smt/smt.yml', Yaml::dump($config, 4, 2));

    return PreferencesFactory::create([
      'home' => $home,
      'global' => TRUE,
      'mounts_list_type' => $mountsListType,
    ]);
  }

  /**
   * @param list<string> $lines
   */
  private function mountOutput(array $lines): string {
    return implode("\n", $lines) . "\n";
  }

}
