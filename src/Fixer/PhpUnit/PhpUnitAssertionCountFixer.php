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
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
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
            'Use PHPUnit assertion `expectNotToPerformAssertion` instead of `addToAssertionCount(1)` when only one assertion would be performed.',
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
                new CodeSample(
                    <<<'PHP'
                        <?php
                        final class MyTest extends \PHPUnit_Framework_TestCase
                        {
                            public function testFix(): void
                            {
                                if (foo()) {
                                    $this->expectNotToPerformAssertions();
                                    return;
                                }

                                static::assertSame(bar());
                            }
                        }

                        PHP,
                ),
            ],
        );
    }

    protected function applyPhpUnitClassFix(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        for ($index = $startIndex; $index < $endIndex; ++$index) {
            $startTokenIndex = $tokens->getNextTokenOfKind($index, self::START_TOKENS);
            # Don't need to recalculate $nextSequence if the previous loop skipped a block
            $nextSequence = $nextSequence ?? $tokens->findSequence([[\T_VARIABLE, '$this'], [\T_OBJECT_OPERATOR, '->'], [\T_STRING, 'addToAssertionCount']], $index);
            if ($nextSequence === null) {
                break;
            }
            $firstSequenceToken = array_keys($nextSequence)[0];
            if ($startTokenIndex && $startTokenIndex < $firstSequenceToken) {
                $startTokenId = $tokens[$startTokenIndex]->getId();
                # Skips for, if, and while loops
                $index = $this->getEndOfBlock($tokens, $startTokenIndex, $endIndex, self::END_TOKENS[$startTokenId]);
                continue;
            }
            $openingParenthesis = $tokens->getNextMeaningfulToken($firstSequenceToken+2);
            $closingParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingParenthesis);

            $index = $closingParenthesis;
            $nextSequence = null;

            $arguments = $argumentsAnalyzer->getArguments($tokens, $openingParenthesis, $closingParenthesis);
            if (1 !== \count($arguments)) {
                continue;
            }
            $argumentTokenIndex = array_pop($arguments);
            $argumentToken = $tokens[$argumentTokenIndex];
            if ($argumentToken->equals([\T_LNUMBER, '1'], false)) {
                $tokens[$firstSequenceToken+2] = new Token([\T_STRING, 'expectNotToPerformAssertions']);
                $tokens->clearAt($argumentTokenIndex);
            }
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
