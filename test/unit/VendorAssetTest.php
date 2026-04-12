<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\VendorAsset;
use stdClass;

#[CoversClass(VendorAsset::class)]
class VendorAssetTest extends TestCase
{
    public function testConstructor(): void
    {
        $asset = new VendorAsset(
            package: 'tinymce/tinymce',
            type: 'js',
            source: '',
            target: 'tinymce',
        );

        $this->assertSame('tinymce/tinymce', $asset->package);
        $this->assertSame('js', $asset->type);
        $this->assertSame('', $asset->source);
        $this->assertSame('tinymce', $asset->target);
    }

    public function testConstructorWithSource(): void
    {
        $asset = new VendorAsset(
            package: 'vendor/package',
            type: 'js',
            source: 'dist/js',
            target: 'mylib',
        );

        $this->assertSame('dist/js', $asset->source);
    }

    public function testFromStdClass(): void
    {
        $data = new stdClass();
        $data->package = 'tinymce/tinymce';
        $data->type = 'js';
        $data->source = '';
        $data->target = 'tinymce';

        $asset = VendorAsset::fromStdClass($data);

        $this->assertSame('tinymce/tinymce', $asset->package);
        $this->assertSame('js', $asset->type);
        $this->assertSame('', $asset->source);
        $this->assertSame('tinymce', $asset->target);
    }

    public function testFromStdClassWithMissingSource(): void
    {
        $data = new stdClass();
        $data->package = 'tinymce/tinymce';
        $data->type = 'js';
        $data->target = 'tinymce';

        $asset = VendorAsset::fromStdClass($data);

        $this->assertSame('', $asset->source);
    }

    public function testToStdClass(): void
    {
        $asset = new VendorAsset(
            package: 'tinymce/tinymce',
            type: 'js',
            source: '',
            target: 'tinymce',
        );

        $obj = $asset->toStdClass();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertSame('tinymce/tinymce', $obj->package);
        $this->assertSame('js', $obj->type);
        $this->assertSame('tinymce', $obj->target);
        $this->assertObjectNotHasProperty('source', $obj);
    }

    public function testToStdClassWithSource(): void
    {
        $asset = new VendorAsset(
            package: 'vendor/package',
            type: 'js',
            source: 'dist',
            target: 'mylib',
        );

        $obj = $asset->toStdClass();

        $this->assertSame('dist', $obj->source);
    }

    public function testToArray(): void
    {
        $asset = new VendorAsset(
            package: 'tinymce/tinymce',
            type: 'js',
            source: '',
            target: 'tinymce',
        );

        $arr = $asset->toArray();

        $this->assertSame('tinymce/tinymce', $arr['package']);
        $this->assertSame('js', $arr['type']);
        $this->assertSame('tinymce', $arr['target']);
        $this->assertArrayNotHasKey('source', $arr);
    }

    public function testToArrayWithSource(): void
    {
        $asset = new VendorAsset(
            package: 'vendor/package',
            type: 'js',
            source: 'dist',
            target: 'mylib',
        );

        $arr = $asset->toArray();

        $this->assertSame('dist', $arr['source']);
    }

    public function testRoundTrip(): void
    {
        $asset = new VendorAsset(
            package: 'tinymce/tinymce',
            type: 'js',
            source: 'dist/js',
            target: 'tinymce',
        );

        $rebuilt = VendorAsset::fromStdClass($asset->toStdClass());

        $this->assertSame($asset->package, $rebuilt->package);
        $this->assertSame($asset->type, $rebuilt->type);
        $this->assertSame($asset->source, $rebuilt->source);
        $this->assertSame($asset->target, $rebuilt->target);
    }
}
