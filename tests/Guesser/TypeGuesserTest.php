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
use Sonata\AdminBundle\Templating\TemplateRegistry;
use Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Symfony\Component\Form\Guess\Guess;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class TypeGuesserTest extends TestCase
{
    private $modelManager;
    private $guesser;

    protected function setUp(): void
    {
        $this->modelManager = $this->createStub(ModelManager::class);
        $this->guesser = new TypeGuesser();
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     */
    public function testGuessTypeNoMetadata(): void
    {
        $class = 'FakeClass';
        $property = 'fakeProperty';

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willThrowException(new MappingException());

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        static::assertSame('text', $result->getType());
        static::assertSame(Guess::LOW_CONFIDENCE, $result->getConfidence());
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @dataProvider associationData
     */
    public function testGuessTypeWithAssociation($mappingType, $type): void
    {
        $property = 'fakeProperty';
        $class = 'FakeClass';

        $classMetadata = $this->createStub(ClassMetadata::class);

        $classMetadata->method('hasAssociation')->with($property)->willReturn(true);
        $classMetadata->method('getAssociationMapping')->with($property)->willReturn(['type' => $mappingType]);

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        static::assertSame($type, $result->getType());
        static::assertSame(Guess::HIGH_CONFIDENCE, $result->getConfidence());
    }

    /**
     * NEXT_MAJOR: Remove this dataProvider.
     */
    public function associationData()
    {
        return [
            'many-to-one' => [
                ClassMetadata::MANY_TO_ONE,
                'orm_many_to_one',
            ],
            'one-to-many' => [
                ClassMetadata::ONE_TO_MANY,
                'orm_one_to_many',
            ],
            'one-to-one' => [
                ClassMetadata::ONE_TO_ONE,
                'orm_one_to_one',
            ],
            'many-to-many' => [
                ClassMetadata::MANY_TO_MANY,
                'orm_many_to_many',
            ],
        ];
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @dataProvider noAssociationData
     */
    public function testGuessTypeNoAssociation($type, $resultType, $confidence): void
    {
        $property = 'fakeProperty';
        $class = 'FakeClass';

        $classMetadata = $this->createStub(ClassMetadata::class);

        $classMetadata->method('hasAssociation')->with($property)->willReturn(false);
        $classMetadata->method('getTypeOfField')->with($property)->willReturn($type);

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        static::assertSame($resultType, $result->getType());
        static::assertSame($confidence, $result->getConfidence());
    }

    /**
     * NEXT_MAJOR: Remove this dataProvider.
     */
    public function noAssociationData()
    {
        return [
            'array' => [
                $array = 'array',
                $array,
                Guess::HIGH_CONFIDENCE,
            ],
            'simple_array' => [
                'simple_array',
                $array,
                Guess::HIGH_CONFIDENCE,
            ],
            'json_array' => [
                'json_array',
                $array,
                Guess::HIGH_CONFIDENCE,
            ],
            'json' => [
                'json',
                $array,
                Guess::HIGH_CONFIDENCE,
            ],
            'boolean' => [
                $boolean = 'boolean',
                $boolean,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetime' => [
                $datetime = 'datetime',
                $datetime,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetime_immutable' => [
                'datetime_immutable',
                $datetime,
                Guess::HIGH_CONFIDENCE,
            ],
            'vardatetime' => [
                'vardatetime',
                $datetime,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz' => [
                'datetimetz',
                $datetime,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz_immutable' => [
                'datetimetz_immutable',
                $datetime,
                Guess::HIGH_CONFIDENCE,
            ],
            'date' => [
                $date = 'date',
                $date,
                Guess::HIGH_CONFIDENCE,
            ],
            'date_immutable' => [
                'date_immutable',
                $date,
                Guess::HIGH_CONFIDENCE,
            ],
            'decimal' => [
                'decimal',
                $number = 'number',
                Guess::MEDIUM_CONFIDENCE,
            ],
            'float' => [
                'float',
                $number,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'integer' => [
                $integer = 'integer',
                $integer,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'bigint' => [
                'bigint',
                $integer,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'smallint' => [
                'smallint',
                $integer,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'string' => [
                'string',
                $text = 'text',
                Guess::MEDIUM_CONFIDENCE,
            ],
            'text' => [
                'text',
                'textarea',
                Guess::MEDIUM_CONFIDENCE,
            ],
            'time' => [
                $time = 'time',
                $time,
                Guess::HIGH_CONFIDENCE,
            ],
            'time_immutable' => [
                'time_immutable',
                $time,
                Guess::HIGH_CONFIDENCE,
            ],
            'somefake' => [
                'somefake',
                $text,
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

        $guess = $this->guesser->guess($fieldDescription);

        static::assertSame($expectedType, $guess->getType());
        static::assertSame($expectedOptions, $guess->getOptions());
        static::assertSame($expectedConfidence, $guess->getConfidence());
    }

    public function guessDataProvider(): iterable
    {
        yield [
            null,
            TemplateRegistry::TYPE_TEXT,
            [],
            Guess::LOW_CONFIDENCE,
        ];

        yield [
            'array',
            FieldDescriptionInterface::TYPE_ARRAY,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'time',
            FieldDescriptionInterface::TYPE_TIME,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'boolean',
            FieldDescriptionInterface::TYPE_BOOLEAN,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'datetime',
            FieldDescriptionInterface::TYPE_DATETIME,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'date',
            FieldDescriptionInterface::TYPE_DATE,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            'float',
            'number',
            [],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            'integer',
            FieldDescriptionInterface::TYPE_INTEGER,
            [],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            'string',
            TemplateRegistry::TYPE_TEXT,
            [],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            'text',
            FieldDescriptionInterface::TYPE_TEXTAREA,
            [],
            Guess::MEDIUM_CONFIDENCE,
        ];

        yield [
            ClassMetadata::ONE_TO_ONE,
            'orm_one_to_one',
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            ClassMetadata::ONE_TO_MANY,
            'orm_one_to_many',
            [],
            Guess::HIGH_CONFIDENCE,
        ];
    }
}
