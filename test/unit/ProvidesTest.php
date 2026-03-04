<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\Provides;
use stdClass;

#[CoversClass(Provides::class)]
class ProvidesTest extends TestCase
{
    public function testCreateEmpty(): void
    {
        $provides = new Provides();

        $this->assertEquals([], $provides->getAll());
        $this->assertFalse($provides->has('psr/log-implementation'));
        $this->assertNull($provides->get('psr/log-implementation'));
    }

    public function testCreateWithImplementations(): void
    {
        $provides = new Provides([
            'psr/log-implementation' => '3.0.0',
            'psr/container-implementation' => '2.0.1',
        ]);

        $this->assertTrue($provides->has('psr/log-implementation'));
        $this->assertEquals('3.0.0', $provides->get('psr/log-implementation'));
        $this->assertTrue($provides->has('psr/container-implementation'));
        $this->assertEquals('2.0.1', $provides->get('psr/container-implementation'));
    }

    public function testGetAll(): void
    {
        $implementations = [
            'psr/log-implementation' => '3.0.0',
            'psr/container-implementation' => '2.0.1',
        ];
        $provides = new Provides($implementations);

        $this->assertEquals($implementations, $provides->getAll());
    }

    public function testFromStdClass(): void
    {
        $data = new stdClass();
        $data->{'psr/log-implementation'} = '3.0.0';
        $data->{'psr/container-implementation'} = '2.0.1';

        $provides = Provides::fromStdClass($data);

        $this->assertTrue($provides->has('psr/log-implementation'));
        $this->assertEquals('3.0.0', $provides->get('psr/log-implementation'));
    }

    public function testToStdClass(): void
    {
        $provides = new Provides([
            'psr/log-implementation' => '3.0.0',
        ]);

        $obj = $provides->toStdClass();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertEquals('3.0.0', $obj->{'psr/log-implementation'});
    }

    public function testToArray(): void
    {
        $implementations = [
            'psr/log-implementation' => '3.0.0',
            'psr/container-implementation' => '2.0.1',
        ];
        $provides = new Provides($implementations);

        $this->assertEquals($implementations, $provides->toArray());
    }

    public function testRoundTrip(): void
    {
        $original = [
            'psr/log-implementation' => '3.0.0',
            'psr/container-implementation' => '2.0.1',
        ];

        $provides = new Provides($original);
        $stdClass = $provides->toStdClass();
        $rebuilt = Provides::fromStdClass($stdClass);

        $this->assertEquals($original, $rebuilt->getAll());
    }

    public function testGetMissingInterface(): void
    {
        $provides = new Provides([
            'psr/log-implementation' => '3.0.0',
        ]);

        $this->assertNull($provides->get('psr/container-implementation'));
        $this->assertFalse($provides->has('psr/container-implementation'));
    }
}
