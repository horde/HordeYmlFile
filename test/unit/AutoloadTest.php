<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\Autoload;
use stdClass;

#[CoversClass(Autoload::class)]
class AutoloadTest extends TestCase
{
    public function testCreateEmpty(): void
    {
        $autoload = new Autoload();

        $this->assertEquals([], $autoload->getPsr4());
        $this->assertEquals([], $autoload->getClassmap());
        $this->assertEquals([], $autoload->getFiles());
    }

    public function testCreateWithPsr4(): void
    {
        $autoload = new Autoload(
            psr4: ['Horde\\Example\\' => 'src/']
        );

        $this->assertEquals(['Horde\\Example\\' => 'src/'], $autoload->getPsr4());
        $this->assertEquals([], $autoload->getClassmap());
    }

    public function testCreateWithClassmap(): void
    {
        $autoload = new Autoload(
            classmap: ['lib/', 'legacy/']
        );

        $this->assertEquals([], $autoload->getPsr4());
        $this->assertEquals(['lib/', 'legacy/'], $autoload->getClassmap());
    }

    public function testCreateWithFiles(): void
    {
        $autoload = new Autoload(
            files: ['functions.php', 'helpers.php']
        );

        $this->assertEquals(['functions.php', 'helpers.php'], $autoload->getFiles());
    }

    public function testFromStdClass(): void
    {
        $data = new stdClass();
        $data->{'psr-4'} = new stdClass();
        $data->{'psr-4'}->{'Horde\\Example\\'} = 'src/';
        $data->classmap = ['lib/'];
        $data->files = ['functions.php'];

        $autoload = Autoload::fromStdClass($data);

        $this->assertEquals(['Horde\\Example\\' => 'src/'], $autoload->getPsr4());
        $this->assertEquals(['lib/'], $autoload->getClassmap());
        $this->assertEquals(['functions.php'], $autoload->getFiles());
    }

    public function testToStdClass(): void
    {
        $autoload = new Autoload(
            psr4: ['Horde\\Example\\' => 'src/'],
            classmap: ['lib/']
        );

        $obj = $autoload->toStdClass();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertObjectHasProperty('psr-4', $obj);
        $this->assertEquals('src/', $obj->{'psr-4'}->{'Horde\\Example\\'});
        $this->assertEquals(['lib/'], $obj->classmap);
    }

    public function testToStdClassOmitsEmpty(): void
    {
        $autoload = new Autoload(
            psr4: ['Horde\\Example\\' => 'src/']
            // classmap and files are empty
        );

        $obj = $autoload->toStdClass();

        $this->assertObjectHasProperty('psr-4', $obj);
        $this->assertObjectNotHasProperty('classmap', $obj);
        $this->assertObjectNotHasProperty('files', $obj);
    }

    public function testToArray(): void
    {
        $autoload = new Autoload(
            psr4: ['Horde\\Example\\' => 'src/'],
            classmap: ['lib/'],
            files: ['functions.php']
        );

        $array = $autoload->toArray();

        $this->assertArrayHasKey('psr-4', $array);
        $this->assertEquals(['Horde\\Example\\' => 'src/'], $array['psr-4']);
        $this->assertArrayHasKey('classmap', $array);
        $this->assertEquals(['lib/'], $array['classmap']);
        $this->assertArrayHasKey('files', $array);
        $this->assertEquals(['functions.php'], $array['files']);
    }

    public function testRoundTrip(): void
    {
        $original = new Autoload(
            psr4: ['Horde\\Example\\' => 'src/', 'Horde\\Test\\' => 'test/'],
            classmap: ['lib/'],
            files: ['functions.php']
        );

        $stdClass = $original->toStdClass();
        $rebuilt = Autoload::fromStdClass($stdClass);

        $this->assertEquals($original->getPsr4(), $rebuilt->getPsr4());
        $this->assertEquals($original->getClassmap(), $rebuilt->getClassmap());
        $this->assertEquals($original->getFiles(), $rebuilt->getFiles());
    }

    public function testMultiplePsr4Namespaces(): void
    {
        $autoload = new Autoload(
            psr4: [
                'Horde\\Example\\' => 'src/',
                'Horde\\Test\\' => 'test/',
                'Horde\\Legacy\\' => 'lib/',
            ]
        );

        $psr4 = $autoload->getPsr4();
        $this->assertCount(3, $psr4);
        $this->assertEquals('src/', $psr4['Horde\\Example\\']);
        $this->assertEquals('test/', $psr4['Horde\\Test\\']);
        $this->assertEquals('lib/', $psr4['Horde\\Legacy\\']);
    }
}
