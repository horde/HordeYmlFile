<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\Dependencies;
use Horde\HordeYmlFile\DependencySet;
use stdClass;

#[CoversClass(Dependencies::class)]
#[CoversClass(DependencySet::class)]
class DependenciesTest extends TestCase
{
    public function testCreateEmpty(): void
    {
        $deps = new Dependencies();

        $this->assertNull($deps->getRequired());
        $this->assertNull($deps->getOptional());
        $this->assertNull($deps->getDev());
        $this->assertTrue($deps->isEmpty());
    }

    public function testCreateWithRequired(): void
    {
        $required = new DependencySet(php: '^8.2');
        $deps = new Dependencies(required: $required);

        $this->assertNotNull($deps->getRequired());
        $this->assertEquals('^8.2', $deps->getRequired()->getPhp());
        $this->assertNull($deps->getOptional());
        $this->assertFalse($deps->isEmpty());
    }

    public function testFromStdClass(): void
    {
        $data = new stdClass();
        $data->required = new stdClass();
        $data->required->php = '^8.2';
        $data->required->composer = new stdClass();
        $data->required->composer->{'horde/exception'} = '^3';
        $data->required->ext = ['json', 'mbstring'];

        $deps = Dependencies::fromStdClass($data);

        $this->assertNotNull($deps->getRequired());
        $this->assertEquals('^8.2', $deps->getRequired()->getPhp());
        $this->assertEquals(['horde/exception' => '^3'], $deps->getRequired()->getComposerPackages());
        $this->assertEquals(['json', 'mbstring'], $deps->getRequired()->getExtensions());
    }

    public function testConvenienceMethods(): void
    {
        $required = new DependencySet(
            php: '^8.2',
            composer: ['horde/exception' => '^3'],
            ext: ['json', 'mbstring']
        );
        $deps = new Dependencies(required: $required);

        $this->assertEquals('^8.2', $deps->getRequiredPhp());
        $this->assertEquals(['horde/exception' => '^3'], $deps->getRequiredComposer());
        $this->assertEquals(['json', 'mbstring'], $deps->getRequiredExtensions());
    }

    public function testConvenienceMethodsReturnEmptyWhenNoRequired(): void
    {
        $deps = new Dependencies();

        $this->assertEquals('', $deps->getRequiredPhp());
        $this->assertEquals([], $deps->getRequiredComposer());
        $this->assertEquals([], $deps->getRequiredExtensions());
        $this->assertEquals([], $deps->getRequiredPear());
    }

    public function testToStdClass(): void
    {
        $required = new DependencySet(
            php: '^8.2',
            composer: ['horde/exception' => '^3']
        );
        $deps = new Dependencies(required: $required);

        $obj = $deps->toStdClass();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertObjectHasProperty('required', $obj);
        $this->assertEquals('^8.2', $obj->required->php);
        $this->assertEquals('^3', $obj->required->composer->{'horde/exception'});
    }

    public function testToArray(): void
    {
        $required = new DependencySet(
            php: '^8.2',
            composer: ['horde/exception' => '^3']
        );
        $deps = new Dependencies(required: $required);

        $array = $deps->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('required', $array);
        $this->assertEquals('^8.2', $array['required']['php']);
        $this->assertEquals('^3', $array['required']['composer']['horde/exception']);
    }

    public function testDependencySetWithPear(): void
    {
        $depSet = new DependencySet(
            pear: [
                'pear.horde.org/Horde_Auth' => '*',
                'pear.horde.org/Horde_Core' => '*',
            ]
        );

        $this->assertEquals(
            [
                'pear.horde.org/Horde_Auth' => '*',
                'pear.horde.org/Horde_Core' => '*',
            ],
            $depSet->getPearPackages()
        );
    }

    public function testDependencySetIsEmpty(): void
    {
        $empty = new DependencySet();
        $this->assertTrue($empty->isEmpty());

        $notEmpty = new DependencySet(php: '^8.2');
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testAllThreeSets(): void
    {
        $required = new DependencySet(php: '^8.2');
        $optional = new DependencySet(ext: ['gd']);
        $dev = new DependencySet(composer: ['horde/test' => '^3']);

        $deps = new Dependencies(
            required: $required,
            optional: $optional,
            dev: $dev
        );

        $this->assertNotNull($deps->getRequired());
        $this->assertNotNull($deps->getOptional());
        $this->assertNotNull($deps->getDev());
        $this->assertFalse($deps->isEmpty());
    }

    public function testRoundTripStdClass(): void
    {
        $data = new stdClass();
        $data->required = new stdClass();
        $data->required->php = '^8.2';
        $data->required->composer = new stdClass();
        $data->required->composer->{'horde/exception'} = '^3';

        $deps = Dependencies::fromStdClass($data);
        $rebuilt = $deps->toStdClass();

        $this->assertEquals($data->required->php, $rebuilt->required->php);
        $this->assertEquals(
            $data->required->composer->{'horde/exception'},
            $rebuilt->required->composer->{'horde/exception'}
        );
    }
}
