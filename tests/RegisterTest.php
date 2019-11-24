<?php

namespace Anper\Pdo\StatementCollector\Tests;

use Anper\Pdo\StatementCollector\Collector;
use Anper\Pdo\StatementCollector\Exception;
use Anper\Pdo\StatementCollector\Statement;
use PHPUnit\Framework\TestCase;

use function Anper\Pdo\StatementCollector\clear_pdo_collectors;
use function Anper\Pdo\StatementCollector\get_pdo_collectors;
use function Anper\Pdo\StatementCollector\register_pdo_collector;
use function Anper\Pdo\StatementCollector\unregister_pdo_collector;

/**
 * Class RegisterPdoCollectorTest
 * @package Anper\PdoCollector\Tests
 */
class RegisterTest extends TestCase
{
    use PdoTrait {
        setUp as parentSetUp;
    }

    protected function setUp(): void
    {
        $this->parentSetUp();

        clear_pdo_collectors($this->pdo);
    }

    /**
     * @param bool $result
     *
     * @return \PDO
     */
    protected function pdo(bool $result = true)
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('setAttribute')
            ->willReturn($result);

        return $pdo;
    }

    public function testRegister(): void
    {
        $collector1 = function ($a) {
        };
        $collector2 = function ($b) {
        };

        $result1 = register_pdo_collector($this->pdo, $collector1);
        $result2 = register_pdo_collector($this->pdo, $collector2);

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        $this->assertStatement();

        $this->assertSame([$collector1, $collector2], get_pdo_collectors($this->pdo));
    }

    public function testRegisterClassCollector(): void
    {
        $collector1 = new Collector($this->pdo);
        $collector2 = new Collector($this->pdo);

        $this->assertStatement();

        $this->assertSame([$collector1, $collector2], get_pdo_collectors($this->pdo));
    }

    /**
     * @param $collector
     */
    protected function assertStatement(): void
    {
        $attr = $this->pdo->getAttribute(\PDO::ATTR_STATEMENT_CLASS);

        $this->assertIsArray($attr);
        $this->assertEquals($attr[0], Statement::class);
        $this->assertIsArray($attr[1]);
        $this->assertIsCallable($attr[1][0]);
    }

    public function testPrependRegister(): void
    {
        $pdo = $this->pdo();

        $collector1 = function ($a) {
        };
        $collector2 = function ($b) {
        };

        register_pdo_collector($pdo, $collector1);
        register_pdo_collector($pdo, $collector2, true, true);

        $this->assertSame([$collector2, $collector1], get_pdo_collectors($pdo));
    }

    public function testRegisterWithException(): void
    {
        $pdo = $this->pdo(false);

        $this->expectException(Exception::class);

        $collector = function () {
        };

        register_pdo_collector($pdo, $collector, true);
    }

    public function testFailedRegister(): void
    {
        $pdo = $this->pdo(false);

        $collector = function () {
        };

        $result = register_pdo_collector($pdo, $collector, false);

        $this->assertNotContains($collector, get_pdo_collectors($pdo));
        $this->assertFalse($result);
    }

    public function testUnregister(): void
    {
        $pdo = $this->pdo();

        $collector1 = function ($a) {
        };
        $collector2 = function ($b) {
        };

        register_pdo_collector($pdo, $collector1);
        register_pdo_collector($pdo, $collector2);

        $this->assertSame([$collector1, $collector2], get_pdo_collectors($pdo));

        $result = unregister_pdo_collector($pdo, $collector1);

        $this->assertTrue($result);

        $this->assertEquals([$collector2], get_pdo_collectors($pdo));
    }
}