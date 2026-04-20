<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use SSHFSMountTool\Util\PathExpander;

final class PathExpanderTest extends TestCase {

  public function testExpandsTildePrefix(): void {
    $expander = new PathExpander('/home/iam');
    self::assertSame('/home/iam/mnt/msrv', $expander->expand('~/mnt/msrv'));
  }

  public function testLeavesAbsolutePathUnchanged(): void {
    $expander = new PathExpander('/home/iam');
    self::assertSame('/mnt/msrv', $expander->expand('/mnt/msrv'));
  }

  public function testLeavesRelativePathUnchanged(): void {
    $expander = new PathExpander('/home/iam');
    self::assertSame('mnt/msrv', $expander->expand('mnt/msrv'));
  }

  public function testOnlyExpandsLeadingTilde(): void {
    $expander = new PathExpander('/home/iam');
    self::assertSame('foo/~/bar', $expander->expand('foo/~/bar'));
  }

}
