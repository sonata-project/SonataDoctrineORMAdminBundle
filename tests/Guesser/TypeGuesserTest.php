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

    public function setUp(): void
    {
        $this->modelManager = $this->prophesize(ModelManager::class);
        $this->guesser = new TypeGuesser();
    }

    public function testGuessTypeNoMetadata(): void
    {
        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property = 'fakeProperty'
        )->willThrow(MappingException::class);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $this->assertSame('text', $result->getType());
        $this->assertSame(Guess::LOW_CONFIDENCE, $result->getConfidence());
    }

    /**
     * @dataProvider associationData
     */
    public function testGuessTypeWithAssociation($mappingType, $type): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $classMetadata->hasAssociation($property = 'fakeProperty')->willReturn(true);
        $classMetadata->getAssociationMapping($property)
            ->willReturn(['type' => $mappingType]);

        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property
        )->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $this->assertSame($type, $result->getType());
        $this->assertSame(Guess::HIGH_CONFIDENCE, $result->getConfidence());
    }

    public function associationData(): array
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
     * @dataProvider noAssociationData
     */
    public function testGuessTypeNoAssociation($type, $resultType, $confidence): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $classMetadata->hasAssociation($property = 'fakeProperty')->willReturn(false);
        $classMetadata->getTypeOfField($property)->willReturn($type);

        $this->modelManager->getParentMetadataForProperty(
            $class = 'FakeClass',
            $property
        )->willReturn([$classMetadata, $property, 'notUsed']);

        $result = $this->guesser->guessType($class, $property, $this->modelManager->reveal());

        $this->assertSame($resultType, $result->getType());
        $this->assertSame($confidence, $result->getConfidence());
    }

    public function noAssociationData(): array
    {
        return [
            'array' => [
                $array = 'array',
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
}
