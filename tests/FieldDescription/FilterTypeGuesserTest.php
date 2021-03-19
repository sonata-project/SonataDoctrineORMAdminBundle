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

namespace Sonata\DoctrineORMAdminBundle\Tests\FieldDescription;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\FieldDescription\FilterTypeGuesser;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Guess\Guess;

class FilterTypeGuesserTest extends TestCase
{
    /**
     * @var FilterTypeGuesser
     */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new FilterTypeGuesser();
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
        $fieldDescription->method('getTargetModel')->willReturn('Foo');

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
