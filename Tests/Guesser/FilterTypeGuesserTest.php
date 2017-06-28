<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Guesser;

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Guesser\FilterTypeGuesser;

class FilterTypeGuesserTest extends TestCase
{
    private $guesser;
    private $modelManager;

    protected function setUp()
    {
        $this->guesser = new FilterTypeGuesser();
        $this->modelManager = $this->prophesize('Sonata\DoctrineORMAdminBundle\Model\ModelManager');
        $this->metadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
    }

    /**
     * @expectException Sonata\DoctrineORMAdminBundle\Model\MissingPropertyMetadataException
     */
    public function testThrowsOnMissingField()
    {
        $class = 'My\Model';
        $property = 'whatever';
        $this->modelManager->getParentMetadataForProperty($class, $property)->willReturn([
            $this->metadata->reveal(),
            $property,
            'parent mappings, no idea what it looks like'
        ]);
        $this->guesser->guessType($class, $property, $this->modelManager->reveal());
    }
}
