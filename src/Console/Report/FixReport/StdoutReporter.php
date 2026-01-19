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

    // For stdout format, we expect exactly one file (stdin input)
    if (0 === \count($changed)) {
      // No changes, return empty string
      return '';
    }

    // Get the first (and should be only) file's fixed content
    $fixResult = reset($changed);

    return $fixResult['fixedContent'];
  }
}
