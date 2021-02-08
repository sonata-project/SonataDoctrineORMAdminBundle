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
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser;
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
        $this->modelManager = $this->getMockBuilder(ModelManagerInterface::class)
            ->setMethodsExcept([])
            ->addMethods(['getParentMetadataForProperty'])
            ->getMock()
        ;
        $this->guesser = new TypeGuesser();
    }

    public function testGuessTypeNoMetadata(): void
    {
        $class = 'FakeClass';
        $property = 'fakeProperty';

        $this->modelManager->method('getParentMetadataForProperty')->with($class, $property)
            ->willThrowException(new MappingException());

        $result = $this->guesser->guessType($class, $property, $this->modelManager);

        $this->assertSame(FieldDescriptionInterface::TYPE_STRING, $result->getType());
        $this->assertSame(Guess::LOW_CONFIDENCE, $result->getConfidence());
    }

    /**
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

        $this->assertSame($type, $result->getType());
        $this->assertSame(Guess::HIGH_CONFIDENCE, $result->getConfidence());
    }

    public function associationData(): array
    {
        return [
            'many-to-one' => [
                ClassMetadata::MANY_TO_ONE,
                FieldDescriptionInterface::TYPE_MANY_TO_ONE,
            ],
            'one-to-many' => [
                ClassMetadata::ONE_TO_MANY,
                FieldDescriptionInterface::TYPE_ONE_TO_MANY,
            ],
            'one-to-one' => [
                ClassMetadata::ONE_TO_ONE,
                FieldDescriptionInterface::TYPE_ONE_TO_ONE,
            ],
            'many-to-many' => [
                ClassMetadata::MANY_TO_MANY,
                FieldDescriptionInterface::TYPE_MANY_TO_MANY,
            ],
        ];
    }

    /**
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

        $this->assertSame($resultType, $result->getType());
        $this->assertSame($confidence, $result->getConfidence());
    }

    public function noAssociationData(): array
    {
        return [
            'array' => [
                'array',
                FieldDescriptionInterface::TYPE_ARRAY,
                Guess::HIGH_CONFIDENCE,
            ],
            'simple_array' => [
                'simple_array',
                FieldDescriptionInterface::TYPE_ARRAY,
                Guess::HIGH_CONFIDENCE,
            ],
            'json_array' => [
                'json_array',
                FieldDescriptionInterface::TYPE_ARRAY,
                Guess::HIGH_CONFIDENCE,
            ],
            'json' => [
                'json',
                FieldDescriptionInterface::TYPE_ARRAY,
                Guess::HIGH_CONFIDENCE,
            ],
            'boolean' => [
                'boolean',
                FieldDescriptionInterface::TYPE_BOOLEAN,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetime' => [
                'datetime',
                FieldDescriptionInterface::TYPE_DATETIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetime_immutable' => [
                'datetime_immutable',
                FieldDescriptionInterface::TYPE_DATETIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'vardatetime' => [
                'vardatetime',
                FieldDescriptionInterface::TYPE_DATETIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz' => [
                'datetimetz',
                FieldDescriptionInterface::TYPE_DATETIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'datetimetz_immutable' => [
                'datetimetz_immutable',
                FieldDescriptionInterface::TYPE_DATETIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'date' => [
                'date',
                FieldDescriptionInterface::TYPE_DATE,
                Guess::HIGH_CONFIDENCE,
            ],
            'date_immutable' => [
                'date_immutable',
                FieldDescriptionInterface::TYPE_DATE,
                Guess::HIGH_CONFIDENCE,
            ],
            'decimal' => [
                'decimal',
                FieldDescriptionInterface::TYPE_FLOAT,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'float' => [
                'float',
                FieldDescriptionInterface::TYPE_FLOAT,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'integer' => [
                'integer',
                FieldDescriptionInterface::TYPE_INTEGER,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'bigint' => [
                'bigint',
                FieldDescriptionInterface::TYPE_INTEGER,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'smallint' => [
                'smallint',
                FieldDescriptionInterface::TYPE_INTEGER,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'string' => [
                'string',
                FieldDescriptionInterface::TYPE_STRING,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'text' => [
                'text',
                FieldDescriptionInterface::TYPE_TEXTAREA,
                Guess::MEDIUM_CONFIDENCE,
            ],
            'time' => [
                'time',
                FieldDescriptionInterface::TYPE_TIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'time_immutable' => [
                'time_immutable',
                FieldDescriptionInterface::TYPE_TIME,
                Guess::HIGH_CONFIDENCE,
            ],
            'somefake' => [
                'somefake',
                FieldDescriptionInterface::TYPE_STRING,
                Guess::LOW_CONFIDENCE,
            ],
        ];
    }
}
