=================================
Rule ``php_unit_assertion_count``
=================================

Use PHPUnit assertion ``expectNotToPerformAssertion`` instead of
``addToAssertionCount(1)`` when applicable.

Warning
-------

This rule is RISKY
~~~~~~~~~~~~~~~~~~



Examples
--------

Example #1
~~~~~~~~~~

.. code-block:: diff

   --- Original
   +++ New
    <?php
    final class MyTest extends \PHPUnit_Framework_TestCase
    {
        public function testFix(): void
        {
            if (foo()) {
   -            $this->addToAssertionCount(1);
   +            $this->expectNotToPerformAssertions();
                return;
            }

            static::asertSame(bar());
        }
    }
   \ No newline at end of file

Example #2
~~~~~~~~~~

.. code-block:: diff

   

References
----------

- Fixer class: `PhpCsFixer\\Fixer\\PhpUnit\\PhpUnitAssertionCountFixer <./../../../src/Fixer/PhpUnit/PhpUnitAssertionCountFixer.php>`_
- Test class: `PhpCsFixer\\Tests\\Fixer\\PhpUnit\\PhpUnitAssertionCountFixerTest <./../../../tests/Fixer/PhpUnit/PhpUnitAssertionCountFixerTest.php>`_

The test class defines officially supported behaviour. Each test case is a part of our backward compatibility promise.
