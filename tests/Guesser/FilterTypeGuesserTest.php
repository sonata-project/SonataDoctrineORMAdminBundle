<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Guesser;

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Guesser\FilterTypeGuesser;

class FilterTypeGuesserTest extends TestCase
{
    private $guesser;
    private $modelManager;
    private $metadata;

    protected function setUp()
    {
        $this->guesser = new FilterTypeGuesser();
        $this->modelManager = $this->prophesize('Sonata\DoctrineORMAdminBundle\Model\ModelManager');
        $this->metadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
    }

    public function testThrowsOnMissingField()
    {
        $this->expectException(\Sonata\DoctrineORMAdminBundle\Model\MissingPropertyMetadataException::class);

        $class = 'My\Model';
        $property = 'whatever';
        $this->modelManager->getParentMetadataForProperty($class, $property)->willReturn([
            $this->metadata->reveal(),
            $property,
            'parent mappings, no idea what it looks like',
        ]);
        $this->guesser->guessType($class, $property, $this->modelManager->reveal());
    }
}
