<?php

declare(strict_types=1);

namespace SSHFSMountTool\Tests\Integration\Command;

use SSHFSMountTool\Tests\Integration\IntegrationTestCase;

final class ShellInitCommandTest extends IntegrationTestCase {

  public function testBashWrapperCallsEvalWithCdEval(): void {
    $tester = $this->tester('shell-init');
    $tester->execute(['shell' => 'bash']);

    self::assertCommandSucceeds($tester);
    $display = $tester->getDisplay();
    self::assertStringContainsString('smt()', $display);
    self::assertStringContainsString('command smt cd --eval', $display);
    self::assertStringContainsString('eval "$__smt_cd"', $display);
    self::assertStringContainsString('command smt "$@"', $display);
  }

  public function testZshWrapperMatchesBash(): void {
    $tester = $this->tester('shell-init');
    $tester->execute(['shell' => 'zsh']);

    self::assertCommandSucceeds($tester);
    self::assertStringContainsString('smt()', $tester->getDisplay());
  }

  public function testFishWrapperUsesFishSyntax(): void {
    $tester = $this->tester('shell-init');
    $tester->execute(['shell' => 'fish']);

    self::assertCommandSucceeds($tester);
    $display = $tester->getDisplay();
    self::assertStringContainsString('function smt', $display);
    self::assertStringContainsString('command smt cd --eval $argv[2..-1]', $display);
    self::assertStringContainsString('end', $display);
  }

  public function testUnsupportedShellIsRejected(): void {
    $tester = $this->tester('shell-init');
    $tester->execute(['shell' => 'tcsh']);

    self::assertSame(2, $tester->getStatusCode());
    self::assertStringContainsString('Unsupported shell', $tester->getDisplay());
  }

}
