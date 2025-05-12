<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PhpUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\InvalidHordeYmlFileException;
use Horde\HordeYmlFile\HordeYmlFile;

#[CoversNothing]
class HordeYmlFileTest   extends TestCase
{
    public function testReadEmptyConfigFileWorks(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__, 1) . '/fixtures/empty/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
        $uut = new HordeYmlFile(dirname(__DIR__, 1) . '/fixtures/emptyyamlobject/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
    }

    public function testApplyGracefulUpdates(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__, 1) . '/fixtures/emptyyamlobject/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
        $uut->applyGracefulUpdates();
        $this->assertEquals('horde', $uut->getVendor());          
        $this->assertEquals('emptyyamlobject', $uut->getName());
    }
}