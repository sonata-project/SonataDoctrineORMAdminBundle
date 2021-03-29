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
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Guess\Guess;

class FilterTypeGuesserTest extends TestCase
{
    private $guesser;
    private $modelManager;
    private $metadata;

    protected function setUp(): void
    {
        $this->guesser = new FilterTypeGuesser();
        $this->modelManager = $this->createStub(ModelManager::class);
        $this->metadata = $this->createStub(ClassMetadata::class);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testThrowsOnMissingField(): void
    {
        $this->expectException(MissingPropertyMetadataException::class);

        $class = 'My\Model';
        $property = 'whatever';
        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)->willReturn([
            $this->metadata,
            $property,
            'parent mappings, no idea what it looks like',
        ]);
        $this->guesser->guessType($class, $property, $this->modelManager);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGuessTypeNoMetadata(): void
    {
        $this->modelManager->method('getParentMetadataForProperty')->with(
            $class = 'FakeClass',
            $property = 'fakeProperty'
        )->willThrowException(new MappingException());

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        $this->assertFalse($result);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGuessTypeWithAssociation(): void
    {
        $property = 'fakeProperty';
        $targetEntity = 'FakeEntity';
        $fieldName = 'fakeName';
        $class = 'FakeClass';
        $parentAssociation = 'parentAssociation';

        $classMetadata = $this->createStub(ClassMetadata::class);

        $classMetadata->method('hasAssociation')->with($property)->willReturn(true);
        $classMetadata->method('getAssociationMapping')->with($property)->willReturn([
            'type' => ClassMetadata::MANY_TO_ONE,
            'targetEntity' => $targetEntity,
            'fieldName' => $fieldName,
        ]);

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willReturn([$classMetadata, $property, $parentAssociation]);

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        $options = $result->getOptions();

        $this->assertSame(ModelFilter::class, $result->getType());
        $this->assertSame(Guess::HIGH_CONFIDENCE, $result->getConfidence());
        $this->assertSame($parentAssociation, $options['parent_association_mappings']);
        $this->assertSame(ClassMetadata::MANY_TO_ONE, $options['mapping_type']);
        $this->assertSame(EqualOperatorType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
        $this->assertSame($fieldName, $options['field_name']);
        $this->assertSame(EntityType::class, $options['field_type']);
        $this->assertSame($targetEntity, $options['field_options']['class']);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @dataProvider noAssociationData
     */
    public function testGuessTypeNoAssociation($type, $resultType, $confidence, $fieldType = null): void
    {
        $property = 'fakeProperty';
        $class = 'FakeClass';

        $classMetadata = $this->createStub(ClassMetadata::class);

        $classMetadata->method('hasAssociation')->with($property)->willReturn(false);

        $classMetadata->fieldMappings = [$property => ['fieldName' => $type]];
        $classMetadata->method('getTypeOfField')->with($property)->willReturn($type);

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        $options = $result->getOptions();

        $this->assertSame($resultType, $result->getType());
        $this->assertSame($type, $options['field_name']);
        $this->assertSame($confidence, $result->getConfidence());

        if ($fieldType) {
            $this->assertSame($fieldType, $options['field_type']);
        } else {
            $this->assertArrayNotHasKey('field_type', $options);
        }
    }

    /**
     * NEXT_MAJOR: Remove this dataProvider.
     */
    public function noAssociationData()
    {
        return [
            'boolean' => [
                'boolean',
                BooleanFilter::class,
                Guess::HIGH_CONFIDENCE,
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
            ],
            'float' => [
                'float',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'integer' => [
                'integer',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                IntegerType::class,
            ],
            'bigint' => [
                'bigint',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                IntegerType::class,
            ],
            'smallint' => [
                'smallint',
                NumberFilter::class,
                Guess::MEDIUM_CONFIDENCE,
                IntegerType::class,
            ],
            'string' => [
                'string',
                StringFilter::class,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'text' => [
                'text',
                StringFilter::class,
                Guess::MEDIUM_CONFIDENCE,
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

    /**
     * @param int|string|null $mappingType
     *
     * @dataProvider guessDataProvider
     */
    public function testGuess(
        $mappingType,
        string $expectedType,
        array $expectedOptions,
        int $expectedConfidence
    ): void {
        $fieldDescription = $this->createStub(FieldDescriptionInterface::class);
        $fieldDescription->method('getFieldName')->willReturn('foo');
        $fieldDescription->method('getMappingType')->willReturn($mappingType);
        $fieldDescription->method('getParentAssociationMappings')->willReturn([]);
        $fieldDescription->method('getTargetEntity')->willReturn('Foo');

        $guess = $this->guesser->guess($fieldDescription);

        $this->assertSame($expectedType, $guess->getType());
        $this->assertSame($expectedOptions, $guess->getOptions());
        $this->assertSame($expectedConfidence, $guess->getConfidence());
    }

    public function guessDataProvider(): iterable
    {
        yield [
            null,
            StringFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::LOW_CONFIDENCE,
        ];

        yield [
            'time',
            TimeFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'boolean',
            BooleanFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'datetime',
            DateTimeFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'date',
            DateFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'float',
            NumberFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            'integer',
            NumberFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => [], 'field_type' => IntegerType::class],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            'string',
            StringFilter::class,
            ['field_name' => 'foo', 'parent_association_mappings' => []],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            ClassMetadata::ONE_TO_ONE,
            ModelFilter::class,
            [
                'field_name' => 'foo',
                'parent_association_mappings' => [],
                'operator_type' => EqualOperatorType::class,
                'operator_options' => [],
                'field_type' => EntityType::class,
                'field_options' => ['class' => 'Foo'],
                'mapping_type' => ClassMetadata::ONE_TO_ONE,
            ],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            ClassMetadata::ONE_TO_MANY,
            ModelFilter::class,
            [
                'field_name' => 'foo',
                'parent_association_mappings' => [],
                'operator_type' => EqualOperatorType::class,
                'operator_options' => [],
                'field_type' => EntityType::class,
                'field_options' => ['class' => 'Foo'],
                'mapping_type' => ClassMetadata::ONE_TO_MANY,
            ],
            Guess::HIGH_CONFIDENCE,
        ];
    }
}
