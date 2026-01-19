<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Console\Report\FixReport;

use PhpCsFixer\Console\Report\FixReport\ReportSummary;
use PhpCsFixer\Console\Report\FixReport\StdoutReporter;
use PhpCsFixer\Tests\TestCase;

/**
 * @author Pomegranate <pomegranaterabbit818@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Console\Report\FixReport\StdoutReporter
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class StdoutReporterTest extends TestCase
{
    public function testGetFormat(): void
    {
        $reporter = new StdoutReporter();
        self::assertSame('stdout', $reporter->getFormat());
    }

    public function testGenerateWithNoChanges(): void
    {
        $reporter = new StdoutReporter();

        $reportSummary = new ReportSummary(
            [],
            1,
            0,
            0,
            false,
            true,
            false
        );

        self::assertSame('', $reporter->generate($reportSummary));
    }

    public function testGenerateWithFixedContent(): void
    {
        $reporter = new StdoutReporter();

        $fixedContent = '<?php

echo "Hello, World!";
';

        $changed = [
            'php://stdin' => [
                'appliedFixers' => ['some_fixer'],
                'diff' => '--- Original
+++ Fixed
@@ @@
-<?php echo "test";
+<?php

+echo "Hello, World!";
',
                'fixedContent' => $fixedContent,
            ],
        ];

        $reportSummary = new ReportSummary(
            $changed,
            1,
            0,
            0,
            false,
            true,
            false
        );

        self::assertSame($fixedContent, $reporter->generate($reportSummary));
    }

    public function testGenerateOnlyOutputsFixedContent(): void
    {
        $reporter = new StdoutReporter();

        $fixedContent = '<?php

namespace App;

class Test
{
    public function run(): void
    {
        echo "test";
    }
}
';

        $changed = [
            'php://stdin' => [
                'appliedFixers' => ['indentation_type', 'blank_line_after_namespace'],
                'diff' => '--- some diff ---',
                'fixedContent' => $fixedContent,
            ],
        ];

        $reportSummary = new ReportSummary(
            $changed,
            1,
            1000,
            1024 * 1024,
            true,
            true,
            true
        );

        $output = $reporter->generate($reportSummary);

        // Should only contain the fixed content, no metadata
        self::assertSame($fixedContent, $output);
        self::assertStringNotContainsString('indentation_type', $output);
        self::assertStringNotContainsString('blank_line_after_namespace', $output);
        self::assertStringNotContainsString('diff', $output);
    }
}
