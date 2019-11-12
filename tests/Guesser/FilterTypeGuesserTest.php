<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Sonata\DoctrineORMAdminBundle\Guesser\FilterTypeGuesser;
use Sonata\DoctrineORMAdminBundle\Model\MissingPropertyMetadataException;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\Form\Type\BooleanType;
use Sonata\Form\Type\EqualType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\Guess;

class FilterTypeGuesserTest extends TestCase
{
    private $guesser;
    private $modelManager;
    private $metadata;

    protected function setUp(): void
    {
        $this->guesser = new FilterTypeGuesser();
        $this->modelManager = $this->prophesize(ModelManager::class);
        $this->metadata = $this->prophesize(ClassMetadata::class);
    }

    public function testThrowsOnMissingField(): void
    {
        $this->expectException(MissingPropertyMetadataException::class);

        $class = 'My\Model';
        $property = 'whatever';
        $this->modelManager->getParentMetadataForProperty($class, $property)->willReturn([
            $this->metadata->reveal(),
            $property,
            'parent mappings, no idea what it looks like',
        ]);
        $this->guesser->guessType($class, $property, $this->modelManager->reveal());
    }

    public function testGuessTypeNoMetadata(): void
    {
        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property = 'fakeProperty'
        )->willThrow(MappingException::class);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $this->assertFalse($result);
    }

    public function testGuessTypeWithAssociation(): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $classMetadata->hasAssociation($property = 'fakeProperty')->willReturn(true);
        $classMetadata->getAssociationMapping($property)
            ->willReturn([
                'type' => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => $targetEntity = 'FakeEntity',
                'fieldName' => $fieldName = 'fakeName',
            ]);

        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property
        )->willReturn([$classMetadata, $property, $parentAssociation = 'parentAssociation']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $options = $result->getOptions();

        $this->assertSame(ModelFilter::class, $result->getType());
        $this->assertSame(Guess::HIGH_CONFIDENCE, $result->getConfidence());
        $this->assertSame($parentAssociation, $options['parent_association_mappings']);
        $this->assertSame(ClassMetadata::MANY_TO_ONE, $options['mapping_type']);
        $this->assertSame(EqualType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
        $this->assertSame($fieldName, $options['field_name']);
        $this->assertSame(EntityType::class, $options['field_type']);
        $this->assertSame($targetEntity, $options['field_options']['class']);
    }

    /**
     * @dataProvider noAssociationData
     */
    public function testGuessTypeNoAssociation($type, $resultType, $confidence, $fieldType = null): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $classMetadata->hasAssociation($property = 'fakeProperty')->willReturn(false);

        $classMetadata->fieldMappings = [$property => ['fieldName' => $type]];
        $classMetadata->getTypeOfField($property)->willReturn($type);

        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property
        )->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $options = $result->getOptions();

        $this->assertSame($resultType, $result->getType());
        $this->assertSame($type, $options['field_name']);
        $this->assertSame($confidence, $result->getConfidence());
        $this->assertSame([], $options['options']);
        $this->assertSame([], $options['field_options']);

        if ($fieldType) {
            $this->assertSame($fieldType, $options['field_type']);
        }
    }

    public function noAssociationData()
    {
        return [
            'boolean' => [
                'boolean',
                BooleanFilter::class,
                Guess::HIGH_CONFIDENCE,
                BooleanType::class,
            ],
            'datetime' => [
                'datetime',
                DateTimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetime_immutable' => [
                'datetime_immutable',
                DateTimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'vardatetime' => [
                'vardatetime',
                DateTimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz' => [
                'datetimetz',
                DateTimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz_immutable' => [
                'datetimetz_immutable',
                DateTimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'date' => [
                'date',
                DateFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'date_immutable' => [
                'date_immutable',
                DateFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'decimal' => [
                'decimal',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                NumberType::class,
            ],
            'float' => [
                'float',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                NumberType::class,
            ],
            'integer' => [
                'integer',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                NumberType::class,
            ],
            'bigint' => [
                'bigint',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                NumberType::class,
            ],
            'smallint' => [
                'smallint',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                NumberType::class,
            ],
            'string' => [
                'string',
                StringFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                TextType::class,
            ],
            'text' => [
                'text',
                StringFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                TextType::class,
            ],
            'time' => [
                'time',
                TimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'time_immutable' => [
                'time_immutable',
                TimeFilter::class,
                Guess::HIGH_CONFIDENCE,
            ],
            'somefake' => [
                'somefake',
                StringFilter::class,
                Guess::LOW_CONFIDENCE,
            ],
        ];
    }
}
