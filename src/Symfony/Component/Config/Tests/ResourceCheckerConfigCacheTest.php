<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\Config\ResourceCheckerInterface;
use Symfony\Component\Config\Tests\Resource\ResourceStub;

class ResourceCheckerConfigCacheTest extends TestCase
{
    private string $cacheFile;
    private string $metaFile;

    protected function setUp(): void
    {
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'config_');
        $this->metaFile = tempnam(sys_get_temp_dir(), 'config_');
    }

    protected function tearDown(): void
    {
        $files = [$this->cacheFile, $this->cacheFile.'.meta', $this->cacheFile.'.meta.json', $this->metaFile, $this->metaFile.'.json'];

        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    public function testGetPath()
    {
        $cache = new ResourceCheckerConfigCache($this->cacheFile);

        $this->assertSame($this->cacheFile, $cache->getPath());
    }

    public function testCacheIsNotFreshIfEmpty()
    {
        $checker = $this->createMock(ResourceCheckerInterface::class)
            ->expects($this->never())->method('supports');

        /* If there is nothing in the cache, it needs to be filled (and thus it's not fresh).
            It does not matter if you provide checkers or not. */

        unlink($this->cacheFile); // remove tempnam() side effect
        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker]);

        $this->assertFalse($cache->isFresh());
    }

    public function testCacheIsFreshIfNoCheckerProvided()
    {
        /* For example in prod mode, you may choose not to run any checkers
           at all. In that case, the cache should always be considered fresh. */
        $cache = new ResourceCheckerConfigCache($this->cacheFile);
        $this->assertTrue($cache->isFresh());
    }

    public function testCacheIsFreshIfEmptyCheckerIteratorProvided()
    {
        $cache = new ResourceCheckerConfigCache($this->cacheFile, new \ArrayIterator([]));
        $this->assertTrue($cache->isFresh());
    }

    public function testResourcesWithoutcheckersAreIgnoredAndConsideredFresh()
    {
        /* As in the previous test, but this time we have a resource. */
        $cache = new ResourceCheckerConfigCache($this->cacheFile);
        $cache->write('', [new ResourceStub()]);

        $this->assertTrue($cache->isFresh()); // no (matching) ResourceChecker passed
    }

    public function testIsFreshWithchecker()
    {
        $checker = $this->createMock(ResourceCheckerInterface::class);

        $checker->expects($this->once())
                  ->method('supports')
                  ->willReturn(true);

        $checker->expects($this->once())
                  ->method('isFresh')
                  ->willReturn(true);

        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker]);
        $cache->write('', [new ResourceStub()]);

        $this->assertTrue($cache->isFresh());
    }

    public function testIsNotFreshWithchecker()
    {
        $checker = $this->createMock(ResourceCheckerInterface::class);

        $checker->expects($this->once())
                  ->method('supports')
                  ->willReturn(true);

        $checker->expects($this->once())
                  ->method('isFresh')
                  ->willReturn(false);

        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker]);
        $cache->write('', [new ResourceStub()]);

        $this->assertFalse($cache->isFresh());
    }

    public function testCacheIsNotFreshWhenUnserializeFails()
    {
        $checker = $this->createMock(ResourceCheckerInterface::class);
        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker]);
        $cache->write('foo', [new FileResource(__FILE__)]);

        $metaFile = "{$this->cacheFile}.meta";
        file_put_contents($metaFile, str_replace('FileResource', 'ClassNotHere', file_get_contents($metaFile)));

        $this->assertFalse($cache->isFresh());
    }

    public function testCacheKeepsContent()
    {
        $cache = new ResourceCheckerConfigCache($this->cacheFile);
        $cache->write('FOOBAR');

        $this->assertSame('FOOBAR', file_get_contents($cache->getPath()));
    }

    public function testCacheIsNotFreshIfNotExistsMetaFile()
    {
        $checker = $this->createMock(ResourceCheckerInterface::class);
        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker]);
        $cache->write('foo', [new FileResource(__FILE__)]);

        $metaFile = "{$this->cacheFile}.meta";
        unlink($metaFile);

        $this->assertFalse($cache->isFresh());
    }

    public function testCacheWithCustomMetaFile()
    {
        $this->assertStringEqualsFile($this->metaFile, '');

        $checker = $this->createMock(ResourceCheckerInterface::class);
        $cache = new ResourceCheckerConfigCache($this->cacheFile, [$checker], $this->metaFile);
        $cache->write('foo', [new FileResource(__FILE__)]);

        $this->assertStringNotEqualsFile($this->metaFile, '');

        $this->assertStringEqualsFile($this->metaFile.'.json', json_encode([
            'resources' => [
                [
                    '@type' => FileResource::class,
                    'resource' => __FILE__,
                ],
            ],
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    }
}
