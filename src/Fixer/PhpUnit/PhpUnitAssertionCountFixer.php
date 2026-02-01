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

namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\Fixer\AbstractPhpUnitFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class PhpUnitAssertionCountFixer extends AbstractPhpUnitFixer
{
    private const START_TOKENS = [[\T_WHILE, 'while'], [\T_IF, 'if'], [\T_FOR, 'for'], [\T_FOREACH, 'foreach']];
    private const START_TOKEN_IDS = [\T_WHILE, \T_IF, \T_FOR, \T_FOREACH];
    private const END_TOKENS = [\T_WHILE => \T_ENDWHILE, \T_IF => \T_ENDIF, \T_FOR => \T_ENDFOR, \T_FOREACH => \T_ENDFOREACH];

    public function isRisky(): bool
    {
        return true;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Use PHPUnit assertion `expectNotToPerformAssertion` instead of `addToAssertionCount(1)` when only one assertion would be claimed.',
            [
                new CodeSample(
                    <<<'PHP'
                        <?php
                        final class MyTest extends \PHPUnit_Framework_TestCase
                        {
                            public function testFix(): void
                            {
                                if (foo()) {
                                    $this->addToAssertionCount(1);
                                    return;
                                }

                                static::assertSame(bar());
                            }
                        }

                        PHP,
                ),
            ],
            'This rule will replace assertions outside of control flows like if, while, and for.',
            'Conversion may be done in situations where it should not happen.',
        );
    }

    protected function applyPhpUnitClassFix(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        for ($index = $startIndex; $index < $endIndex; ++$index) {
            $startTokenIndex = $tokens->getNextTokenOfKind($index, self::START_TOKENS);
            // Don't need to recalculate $nextSequence if the previous loop skipped a block
            $nextSequence ??= $tokens->findSequence([[\T_STRING, 'addToAssertionCount'], '(', [\T_LNUMBER, '1'], ')'], $index);
            if (null === $nextSequence) {
                break;
            }
            $firstSequenceToken = array_keys($nextSequence)[0];
            if ($startTokenIndex && $startTokenIndex < $firstSequenceToken) {
                $startTokenId = $tokens[$startTokenIndex]->getId();
                // Skips for, if, and while loops
                $index = $this->getEndOfBlock($tokens, $startTokenIndex, $endIndex, self::END_TOKENS[$startTokenId]);

                continue;
            }
            // Verify previous tokens to be valid
            $valid = false;
            if (\T_OBJECT_OPERATOR === $tokens[$firstSequenceToken - 1]->getId()) {
                $valid = '$this' === $tokens[$firstSequenceToken - 2]->getContent();
            } elseif (\T_PAAMAYIM_NEKUDOTAYIM === $tokens[$firstSequenceToken - 1]->getId()) {
                $valid = \in_array($tokens[$firstSequenceToken - 2]->getContent(), ['self', 'static'], true);
            }
            if (!$valid) {
                continue;
            }
            $index = $firstSequenceToken + 3;
            $argumentTokenIndex = $firstSequenceToken + 2;
            $tokens[$firstSequenceToken] = new Token([\T_STRING, 'expectNotToPerformAssertions']);
            $tokens->clearAt($argumentTokenIndex);
            $nextSequence = null;
        }
    }

    private function getEndOfBlock(Tokens $tokens, int $index, int $endIndex, int $endToken): int
    {
        $start = $index;
        for (++$index; $index < $endIndex; ++$index) {
            if (null === $index) {
                return \count($tokens);
            }
            $startToken = array_search($tokens[$index]->getId(), self::START_TOKEN_IDS, true);
            if ($startToken) {
                $startTokenId = self::START_TOKEN_IDS[$startToken];
                $this->getEndOfBlock($tokens, $index, $endIndex, self::END_TOKENS[$startTokenId]);
            } elseif ($tokens[$index]->getId() === $endToken) {
                return $index;
            } elseif (\T_RETURN === $tokens[$index]->getId()) {
                return $start;
            }
        }

        return $index;
    }
}
