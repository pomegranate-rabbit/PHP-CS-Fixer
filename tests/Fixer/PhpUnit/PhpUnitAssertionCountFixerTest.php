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

namespace PhpCsFixer\Tests\Fixer\PhpUnit;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\PhpUnit\PhpUnitAssertionCountFixer
 *
 * @extends AbstractFixerTestCase<\PhpCsFixer\Fixer\PhpUnit\PhpUnitAssertionCountFixer>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class PhpUnitAssertionCountFixerTest extends AbstractFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public static function provideFixCases(): iterable
    {
        yield 'default fix' => [
            '<?php
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
}',
            '<?php
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
}',
        ];

        yield 'complex default fix' => [
            '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFix(): void
    {
        if (foo()) {
            $this->expectNotToPerformAssertions();

            return;
        } else {
            static::assertSame(bar2());
        }

        static::assertSame(bar());
    }
}',
            '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFix(): void
    {
        if (foo()) {
            $this->addToAssertionCount(1);

            return;
        } else {
            static::assertSame(bar2());
        }

        static::assertSame(bar());
    }
}',
        ];

        yield 'do not fix condition' => [
            '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFix(): void
    {
        if (foo()) {
            $this->addToAssertionCount(1);
        } else {
            static::assertSame(bar());
        }

        static::assertSame(bar());
    }
}',
        ];

        yield 'do not fix loop' => [
            '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFix(): void
    {
        while (foo()) {
            $this->addToAssertionCount(1);
        }
    }
}',
        ];

        yield 'simple non fixes' => [
            '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo1(): void
    {
        // does not fix when comment or PHPDoc
        // $this->addToAssertionCount(1);
        /* $this->addToAssertionCount(1); */
        /** $this->addToAssertionCount(1); */
    }

    public function testFoo2(): void
    {
        // does not fix when not a method call
        addToAssertionCount(1);
    }

    public function testFoo3(): void
    {
        // does not fix when not a method call
        \addToAssertionCount(1);
    }

    public function testFoo4(): void
    {
        // does not fix when parameter is not 1
        $this->addToAssertionCount(2);
    }

    public function testFoo5(): void
    {
        // does not fix when parameter is not 1
        $this->addToAssertionCount($a);
    }

    public function testFoo6(): void
    {
        // does not fix when on different object
        $foo->addToAssertionCount(1);
    }

    public function testFoo7(): void
    {
        // does not fix when static
        static::addToAssertionCount(1);
    }

    public function testFoo8(): void
    {
        // does not fix when static
        Foo::addToAssertionCount(1);
    }

    public function testFoo9(): void
    {
        // does not fix when static
        self::addToAssertionCount(1);
    }

    public function testFoo10(): void
    {
        // does not fix when number of parameters is not 1
        $this->addToAssertionCount($a, $b);
    }

    public function testFoo11(): void
    {
        // does not fix when number of parameters is not 1
        $this->addToAssertionCount();
    }
}',
        ];
    }
}
