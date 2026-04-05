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

namespace PhpCsFixer\Console\Report\FixReport;

/**
 * @author Pomegranate <pomegranaterabbit818@gmail.com>
 *
 * @readonly
 *
 * @internal
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class StdoutReporter implements ReporterInterface
{
    public function getFormat(): string
    {
        return 'stdout';
    }

    public function generate(ReportSummary $reportSummary): string
    {
        $changed = $reportSummary->getChanged();

        if (1 !== \count($changed)) {
            throw new \RuntimeException(\sprintf(
                'Expected exactly one file in changed array for stdout format, got %d',
                \count($changed),
            ));
        }

        if (!isset($changed['php://stdin'])) {
            throw new \RuntimeException('Missing fix result for php://stdin for stdout format');
        }
        $fixResult = $changed['php://stdin'];

        if (!isset($fixResult['fixedContent'])) {
            throw new \RuntimeException('Missing fixedContent in fix result for stdout format');
        }

        return $fixResult['fixedContent'];
    }
}
