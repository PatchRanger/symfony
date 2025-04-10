<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadata;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonStreamer\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

class PropertyMetadataLoaderTest extends TestCase
{
    public function testReadPropertyType()
    {
        $loader = new PropertyMetadataLoader(TypeResolver::create());

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int()),
            'name' => new PropertyMetadata('name', Type::string()),
        ], $loader->load(ClassicDummy::class));
    }
}
