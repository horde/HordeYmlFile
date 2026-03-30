<?php

declare(strict_types=1);

namespace Horde\HordeYmlFile\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\HordeYmlFile\HordeYmlFile;
use Horde\HordeYmlFile\InvalidHordeYmlFileException;
use Horde\Yaml\Yaml;

/**
 * Comprehensive tests for HordeYmlFile.
 *
 * Note: Many tests will initially fail - this is expected and demonstrates
 * the bugs that need to be fixed in the library.
 */
#[CoversClass(HordeYmlFile::class)]
class HordeYmlFileTest extends TestCase
{
    private string $testDataDir;

    protected function setUp(): void
    {
        $this->testDataDir = dirname(__DIR__) . '/fixtures/generated';
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0o755, true);
        }
    }

    // ========== Basic Loading Tests ==========

    public function testLoadValidFile(): void
    {
        $file = $this->createTempHordeYml([
            'id' => 'TestComponent',
            'name' => 'Test',
            'vendor' => 'horde',
            'type' => 'library',
            'version' => [
                'release' => '1.0.0',
                'api' => '1.0.0',
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('TestComponent', $horde->getId());
        $this->assertEquals('Test', $horde->getName());
    }

    public function testLoadExistingFixtureEmptyFile(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__) . '/fixtures/empty/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
    }

    public function testLoadExistingFixtureEmptyYamlObject(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__) . '/fixtures/emptyyamlobject/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
    }

    public function testLoadExistingFixtureOtherVendor(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__) . '/fixtures/othervendor/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
        $this->assertEquals('maintaina', $uut->getVendor());
        $this->assertEquals('Lancelot', $uut->getName());
        $this->assertEquals('Lancelot', $uut->getId());
        $this->assertEquals('application', $uut->getType());
        $this->assertEquals('maintaina/lancelot', $uut->getComposerName());
        $this->assertEquals('maintaina', $uut->getList());
        $this->assertEquals('Lancelot Project Management', $uut->getFullName());
        $this->assertStringContainsString('simple and elegant', $uut->getDescription());
    }

    public function testLoadMissingFileThrowsException(): void
    {
        $this->expectException(InvalidHordeYmlFileException::class);
        new HordeYmlFile('/nonexistent/file.yml');
    }

    // ========== Version Methods Tests ==========

    public function testGetReleaseVersion(): void
    {
        $file = $this->createTempHordeYml([
            'version' => ['release' => '2.3.4'],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('2.3.4', $horde->getReleaseVersion());
    }

    public function testGetReleaseVersionReturnsEmptyWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('', $horde->getReleaseVersion());
    }

    public function testSetReleaseVersion(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setReleaseVersion('3.0.0');

        $this->assertEquals('3.0.0', $horde->getReleaseVersion());
    }

    public function testGetApiVersion(): void
    {
        $file = $this->createTempHordeYml([
            'version' => ['api' => '1.5.0'],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('1.5.0', $horde->getApiVersion());
    }

    public function testGetApiVersionReturnsEmptyWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('', $horde->getApiVersion());
    }

    public function testSetApiVersion(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setApiVersion('2.0.0');

        $this->assertEquals('2.0.0', $horde->getApiVersion());
    }

    // ========== State Methods Tests ==========

    public function testGetReleaseState(): void
    {
        $file = $this->createTempHordeYml([
            'state' => ['release' => 'stable'],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('stable', $horde->getReleaseState());
    }

    public function testGetReleaseStateDefaultsToAlpha(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('alpha', $horde->getReleaseState());
    }

    public function testSetReleaseState(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setReleaseState('beta');

        $this->assertEquals('beta', $horde->getReleaseState());
    }

    public function testGetApiState(): void
    {
        $file = $this->createTempHordeYml([
            'state' => ['api' => 'stable'],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('stable', $horde->getApiState());
    }

    public function testGetApiStateDefaultsToAlpha(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('alpha', $horde->getApiState());
    }

    public function testSetApiState(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setApiState('stable');

        $this->assertEquals('stable', $horde->getApiState());
    }

    // ========== Name/Vendor/ID Tests ==========

    public function testGetComposerName(): void
    {
        $file = $this->createTempHordeYml([
            'vendor' => 'horde',
            'name' => 'Components',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('horde/components', $horde->getComposerName());
    }

    public function testGetComposerNameWithUpperCase(): void
    {
        $file = $this->createTempHordeYml([
            'vendor' => 'Horde',
            'name' => 'MyComponent',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('horde/mycomponent', $horde->getComposerName());
    }

    public function testGetName(): void
    {
        $file = $this->createTempHordeYml([
            'name' => 'MyComponent',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('MyComponent', $horde->getName());
    }

    public function testSetName(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setName('NewName');

        $this->assertEquals('NewName', $horde->getName());
    }

    public function testGetId(): void
    {
        $file = $this->createTempHordeYml([
            'id' => 'ComponentId',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('ComponentId', $horde->getId());
    }

    public function testSetId(): void
    {
        $file = $this->createTempHordeYml(['name' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setId('NewId');

        $this->assertEquals('NewId', $horde->getId());
    }

    public function testGetVendor(): void
    {
        $file = $this->createTempHordeYml([
            'vendor' => 'myvendor',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('myvendor', $horde->getVendor());
    }

    public function testSetVendor(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setVendor('newvendor');

        $this->assertEquals('newvendor', $horde->getVendor());
    }

    public function testApplyGracefulUpdates(): void
    {
        $uut = new HordeYmlFile(dirname(__DIR__) . '/fixtures/emptyyamlobject/.horde.yml');
        $this->assertInstanceOf(HordeYmlFile::class, $uut);
        $uut->applyGracefulUpdates();
        $this->assertEquals('horde', $uut->getVendor());
        $this->assertEquals('emptyyamlobject', $uut->getName());
    }

    public function testGetChangelog(): void
    {
        $horde = new HordeYmlFile(dirname(__DIR__) . '/fixtures/othervendor/.horde.yml');
        $changelog = $horde->getChangelog();

        $this->assertInstanceOf(\Horde\HordeYmlFile\ChangelogYmlFile::class, $changelog);
        // Verify it loaded the changelog from the correct relative path
        $this->assertTrue($changelog->hasVersion('2.0.0'));
        $this->assertTrue($changelog->hasVersion('1.0.0'));
    }

    // ========== Type and Homepage Tests ==========

    public function testGetType(): void
    {
        $file = $this->createTempHordeYml([
            'type' => 'library',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('library', $horde->getType());
    }

    public function testSetType(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setType('application');

        $this->assertEquals('application', $horde->getType());
    }

    public function testSetTypeMetapackage(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);
        $horde = new HordeYmlFile($file);
        $horde->setType('metapackage');
        $this->assertEquals('metapackage', $horde->getType());
    }

    public function testSetTypeComposerPlugin(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);
        $horde = new HordeYmlFile($file);
        $horde->setType('composer-plugin');
        $this->assertEquals('composer-plugin', $horde->getType());
    }

    public function testSetTypeProject(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);
        $horde = new HordeYmlFile($file);
        $horde->setType('project');
        $this->assertEquals('project', $horde->getType());
    }

    public function testSetTypePhpExt(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);
        $horde = new HordeYmlFile($file);
        $horde->setType('php-ext');
        $this->assertEquals('php-ext', $horde->getType());
    }

    public function testSetTypePhpExtZend(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);
        $horde = new HordeYmlFile($file);
        $horde->setType('php-ext-zend');
        $this->assertEquals('php-ext-zend', $horde->getType());
    }

    public function testGetHomePage(): void
    {
        $file = $this->createTempHordeYml([
            'homepage' => 'https://www.horde.org',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('https://www.horde.org', $horde->getHomePage());
    }

    public function testSetHomePage(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setHomePage('https://example.com');

        $this->assertEquals('https://example.com', $horde->getHomePage());
    }

    // ========== List Tests ==========

    public function testGetList(): void
    {
        $file = $this->createTempHordeYml([
            'list' => 'dev',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('dev', $horde->getList());
    }

    public function testGetListReturnsEmptyWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('', $horde->getList());
    }

    public function testSetList(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setList('horde');

        $this->assertEquals('horde', $horde->getList());
    }

    // ========== Keywords Tests ==========

    public function testGetKeywords(): void
    {
        $file = $this->createTempHordeYml([
            'keywords' => ['http', 'client', 'web'],
        ]);

        $horde = new HordeYmlFile($file);
        $keywords = $horde->getKeywords();

        $this->assertCount(3, $keywords);
        $this->assertContains('http', $keywords);
        $this->assertContains('client', $keywords);
        $this->assertContains('web', $keywords);
    }

    public function testGetKeywordsReturnsEmptyArrayWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals([], $horde->getKeywords());
    }

    public function testSetKeywords(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setKeywords(['keyword1', 'keyword2']);

        $keywords = $horde->getKeywords();
        $this->assertCount(2, $keywords);
        $this->assertContains('keyword1', $keywords);
        $this->assertContains('keyword2', $keywords);
    }

    public function testSetKeywordsEmpty(): void
    {
        $file = $this->createTempHordeYml([
            'keywords' => ['old', 'keywords'],
        ]);

        $horde = new HordeYmlFile($file);
        $horde->setKeywords([]);

        $this->assertEquals([], $horde->getKeywords());
    }

    public function testKeywordsPersistAfterSave(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setKeywords(['persistence', 'test']);
        $horde->save();

        // Reload and verify
        $reloaded = new HordeYmlFile($file);
        $keywords = $reloaded->getKeywords();
        $this->assertCount(2, $keywords);
        $this->assertContains('persistence', $keywords);
        $this->assertContains('test', $keywords);
    }

    public function testGetKeywordsNormalizes(): void
    {
        $file = $this->createTempHordeYml([
            'keywords' => ['HTTP', 'Client', 'WEB', 'http'],  // Mixed case, duplicates
        ]);

        $horde = new HordeYmlFile($file);
        $keywords = $horde->getKeywords();

        // Should be normalized to lowercase and deduplicated
        $this->assertCount(3, $keywords);
        $this->assertContains('http', $keywords);
        $this->assertContains('client', $keywords);
        $this->assertContains('web', $keywords);
    }

    public function testGetKeywordsFiltersGarbage(): void
    {
        $file = $this->createTempHordeYml([
            'keywords' => ['valid', '', '  ', 'also-valid', null],
        ]);

        $horde = new HordeYmlFile($file);
        $keywords = $horde->getKeywords();

        // Should filter out empty strings and null
        $this->assertCount(2, $keywords);
        $this->assertContains('valid', $keywords);
        $this->assertContains('also-valid', $keywords);
    }

    public function testGetKeywordsTrimsWhitespace(): void
    {
        $file = $this->createTempHordeYml([
            'keywords' => ['  spaces  ', 'tabs	', '  mixed  '],
        ]);

        $horde = new HordeYmlFile($file);
        $keywords = $horde->getKeywords();

        $this->assertCount(3, $keywords);
        $this->assertContains('spaces', $keywords);
        $this->assertContains('tabs', $keywords);
        $this->assertContains('mixed', $keywords);
    }

    // ========== Support Tests ==========

    public function testGetSupportReturnsNullWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertNull($horde->getSupport());
    }

    public function testGetSupport(): void
    {
        $file = $this->createTempHordeYml([
            'support' => [
                'email' => 'dev@lists.horde.org',
                'issues' => 'https://github.com/horde/horde/issues',
                'docs' => 'https://www.horde.org/libraries/Horde_Http',
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $support = $horde->getSupport();

        $this->assertInstanceOf(\Horde\HordeYmlFile\Support::class, $support);
        $this->assertEquals('dev@lists.horde.org', $support->getEmail());
        $this->assertEquals('https://github.com/horde/horde/issues', $support->getIssues());
        $this->assertEquals('https://www.horde.org/libraries/Horde_Http', $support->getDocs());
    }

    public function testSetSupport(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $support = (new \Horde\HordeYmlFile\SupportBuilder())
            ->email('test@example.org')
            ->issues('https://example.org/issues')
            ->build();

        $horde = new HordeYmlFile($file);
        $horde->setSupport($support);

        $retrieved = $horde->getSupport();
        $this->assertNotNull($retrieved);
        $this->assertEquals('test@example.org', $retrieved->getEmail());
        $this->assertEquals('https://example.org/issues', $retrieved->getIssues());
    }

    public function testSetSupportEmpty(): void
    {
        $file = $this->createTempHordeYml([
            'support' => [
                'email' => 'old@example.org',
            ],
        ]);

        $emptySupport = new \Horde\HordeYmlFile\Support();

        $horde = new HordeYmlFile($file);
        $horde->setSupport($emptySupport);

        // Empty support should remove the field
        $this->assertNull($horde->getSupport());
    }

    public function testSupportPersistsAfterSave(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $support = (new \Horde\HordeYmlFile\SupportBuilder())
            ->email('persist@example.org')
            ->issues('https://example.org/issues')
            ->docs('https://example.org/docs')
            ->build();

        $horde = new HordeYmlFile($file);
        $horde->setSupport($support);
        $horde->save();

        // Reload and verify
        $reloaded = new HordeYmlFile($file);
        $retrieved = $reloaded->getSupport();

        $this->assertNotNull($retrieved);
        $this->assertEquals('persist@example.org', $retrieved->getEmail());
        $this->assertEquals('https://example.org/issues', $retrieved->getIssues());
        $this->assertEquals('https://example.org/docs', $retrieved->getDocs());
    }

    // ========== Autoload Tests ==========

    public function testGetAutoload(): void
    {
        $file = $this->createTempHordeYml([
            'autoload' => [
                'psr-4' => [
                    'Horde\\Test\\' => 'src/',
                ],
                'classmap' => ['lib/'],
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $autoload = $horde->getAutoload();

        $this->assertNotNull($autoload);
        $this->assertInstanceOf(\Horde\HordeYmlFile\Autoload::class, $autoload);
        $this->assertEquals(['Horde\\Test\\' => 'src/'], $autoload->getPsr4());
        $this->assertEquals(['lib/'], $autoload->getClassmap());
    }

    public function testGetAutoloadReturnsNullWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertNull($horde->getAutoload());
    }

    public function testSetAutoload(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $autoload = new \Horde\HordeYmlFile\Autoload(
            psr4: ['Horde\\Example\\' => 'src/'],
            classmap: [],
            files: []
        );
        $horde->setAutoload($autoload);

        $retrieved = $horde->getAutoload();
        $this->assertNotNull($retrieved);
        $this->assertInstanceOf(\Horde\HordeYmlFile\Autoload::class, $retrieved);
        $this->assertEquals(['Horde\\Example\\' => 'src/'], $retrieved->getPsr4());
    }

    // ========== Provides Tests ==========

    public function testGetProvides(): void
    {
        $file = $this->createTempHordeYml([
            'provides' => [
                'psr/log-implementation' => '3.0.0',
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $provides = $horde->getProvides();

        $this->assertNotNull($provides);
        $this->assertInstanceOf(\Horde\HordeYmlFile\Provides::class, $provides);
        $this->assertTrue($provides->has('psr/log-implementation'));
        $this->assertEquals('3.0.0', $provides->get('psr/log-implementation'));
    }

    public function testGetProvidesReturnsNullWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertNull($horde->getProvides());
    }

    public function testSetProvides(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $provides = new \Horde\HordeYmlFile\Provides([
            'psr/container-implementation' => '2.0.1',
        ]);
        $horde->setProvides($provides);

        $retrieved = $horde->getProvides();
        $this->assertNotNull($retrieved);
        $this->assertInstanceOf(\Horde\HordeYmlFile\Provides::class, $retrieved);
        $this->assertEquals('2.0.1', $retrieved->get('psr/container-implementation'));
    }

    // ========== Full Name and Description Tests ==========

    public function testGetFullName(): void
    {
        $file = $this->createTempHordeYml([
            'full' => 'Full Component Name',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('Full Component Name', $horde->getFullName());
    }

    public function testSetFullName(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setFullName('New Full Name');

        $this->assertEquals('New Full Name', $horde->getFullName());
    }

    public function testGetDescription(): void
    {
        $file = $this->createTempHordeYml([
            'description' => 'A test component',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('A test component', $horde->getDescription());
    }

    public function testSetDescription(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setDescription('New description');

        $this->assertEquals('New description', $horde->getDescription());
    }

    // ========== License Tests ==========

    public function testGetLicense(): void
    {
        $file = $this->createTempHordeYml([
            'license' => [
                'identifier' => 'LGPL-2.1',
                'uri' => 'http://www.horde.org/licenses/lgpl21',
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $license = $horde->getLicense();

        $this->assertNotNull($license);
        $this->assertEquals('LGPL-2.1', $license->identifier);
        $this->assertEquals('http://www.horde.org/licenses/lgpl21', $license->uri);
    }

    public function testGetLicenseReturnsNullWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertNull($horde->getLicense());
    }

    public function testSetLicense(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setLicense('MIT', 'https://opensource.org/licenses/MIT');

        $license = $horde->getLicense();
        $this->assertNotNull($license);
        $this->assertEquals('MIT', $license->identifier);
        $this->assertEquals('https://opensource.org/licenses/MIT', $license->uri);
    }

    // ========== Authors Tests ==========

    public function testGetAuthors(): void
    {
        $file = $this->createTempHordeYml([
            'authors' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $authors = $horde->getAuthors();

        $this->assertCount(2, $authors);
        $this->assertEquals('John Doe', $authors[0]['name']);
        $this->assertEquals('Jane Smith', $authors[1]['name']);
    }

    public function testSetAuthors(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setAuthors([
            ['name' => 'Test Author', 'email' => 'test@example.com'],
        ]);

        $authors = $horde->getAuthors();
        $this->assertCount(1, $authors);
        $this->assertEquals('Test Author', $authors[0]['name']);
    }

    // ========== Dependencies Tests ==========

    public function testGetRequiredPhp(): void
    {
        $file = $this->createTempHordeYml([
            'dependencies' => [
                'required' => [
                    'php' => '^8.2',
                ],
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('^8.2', $horde->getRequiredPhp());
    }

    public function testGetRequiredPhpReturnsEmptyWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('', $horde->getRequiredPhp());
    }

    public function testGetRequiredExtensions(): void
    {
        $file = $this->createTempHordeYml([
            'dependencies' => [
                'required' => [
                    'ext' => ['json', 'mbstring', 'xml'],
                ],
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $extensions = $horde->getRequiredExtensions();

        $this->assertCount(3, $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('mbstring', $extensions);
        $this->assertContains('xml', $extensions);
    }

    public function testGetRequiredExtensionsReturnsEmptyArrayWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals([], $horde->getRequiredExtensions());
    }

    public function testGetDependencies(): void
    {
        $file = $this->createTempHordeYml([
            'dependencies' => [
                'required' => ['php' => '^8.2'],
                'optional' => ['ext' => ['gd']],
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $deps = $horde->getDependencies();

        $this->assertNotNull($deps);
        $this->assertInstanceOf(\Horde\HordeYmlFile\Dependencies::class, $deps);
        $this->assertNotNull($deps->getRequired());
        $this->assertEquals('^8.2', $deps->getRequired()->getPhp());
        $this->assertNotNull($deps->getOptional());
        $this->assertEquals(['gd'], $deps->getOptional()->getExtensions());
    }

    // ========== Allowed Plugins Tests ==========

    public function testGetAllowedPlugins(): void
    {
        $file = $this->createTempHordeYml([
            'allow-plugins' => [
                'vendor/plugin1' => true,
                'vendor/plugin2' => false,
            ],
        ]);

        $horde = new HordeYmlFile($file);
        $plugins = $horde->getAllowedPlugins();

        $this->assertTrue($plugins['vendor/plugin1']);
        $this->assertFalse($plugins['vendor/plugin2']);
    }

    public function testGetAllowedPluginsReturnsEmptyArrayWhenMissing(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals([], $horde->getAllowedPlugins());
    }

    public function testGetAllowedPluginsWithWildcard(): void
    {
        $file = $this->createTempHordeYml([
            'allow-plugins' => true,
        ]);

        $horde = new HordeYmlFile($file);
        $plugins = $horde->getAllowedPlugins();

        $this->assertEquals(['*' => true], $plugins);
    }

    public function testSetAllowedPlugins(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->setAllowedPlugins([
            'vendor/plugin1' => true,
            'vendor/plugin2' => true,
        ]);

        $plugins = $horde->getAllowedPlugins();
        $this->assertTrue($plugins['vendor/plugin1']);
        $this->assertTrue($plugins['vendor/plugin2']);
    }

    // ========== Serialization Tests ==========

    public function testToStringReturnsYaml(): void
    {
        $file = $this->createTempHordeYml([
            'id' => 'Test',
            'name' => 'TestComponent',
            'vendor' => 'horde',
        ]);

        $horde = new HordeYmlFile($file);
        $yaml = (string) $horde;

        $this->assertStringContainsString('id:', $yaml);
        $this->assertStringContainsString('Test', $yaml);
        $this->assertStringContainsString('name:', $yaml);
        $this->assertStringContainsString('TestComponent', $yaml);
    }

    public function testSaveWritesFile(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test', 'name' => 'OriginalName']);

        $horde = new HordeYmlFile($file);
        $horde->setName('UpdatedName');
        $horde->save();

        // Reload and verify
        $reloaded = new HordeYmlFile($file);
        $this->assertEquals('UpdatedName', $reloaded->getName());
    }

    // ========== Raw Access Tests ==========

    public function testGetRawValue(): void
    {
        $file = $this->createTempHordeYml([
            'custom_field' => 'custom_value',
        ]);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('custom_value', $horde->get('custom_field'));
    }

    public function testGetRawValueWithDefault(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $this->assertEquals('default', $horde->get('nonexistent', 'default'));
    }

    public function testSetRawValue(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test']);

        $horde = new HordeYmlFile($file);
        $horde->set('new_field', 'new_value');

        $this->assertEquals('new_value', $horde->get('new_field'));
    }

    public function testHasKey(): void
    {
        $file = $this->createTempHordeYml(['id' => 'test', 'name' => 'TestName']);

        $horde = new HordeYmlFile($file);
        $this->assertTrue($horde->has('id'));
        $this->assertTrue($horde->has('name'));
        $this->assertFalse($horde->has('nonexistent'));
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 'test',
            'name' => 'Test',
            'vendor' => 'horde',
            'type' => 'library',
        ];
        $file = $this->createTempHordeYml($data);

        $horde = new HordeYmlFile($file);
        $array = $horde->toArray();

        $this->assertEquals('test', $array['id']);
        $this->assertEquals('Test', $array['name']);
        $this->assertEquals('horde', $array['vendor']);
        $this->assertEquals('library', $array['type']);
    }

    // ========== Helper Methods ==========

    private function createTempHordeYml(array $data): string
    {
        $file = tempnam($this->testDataDir, 'horde_') . '.yml';
        $yaml = Yaml::dump($data, ['wordwrap' => 78, 'indent' => 2]);
        file_put_contents($file, $yaml);
        return $file;
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->testDataDir)) {
            foreach (glob($this->testDataDir . '/horde_*') as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
