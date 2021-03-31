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
use Sonata\DoctrineORMAdminBundle\FieldDescription\TypeGuesser;
use Symfony\Component\Form\Guess\Guess;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class TypeGuesserTest extends TestCase
{
    /**
     * @var TypeGuesser
     */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new TypeGuesser();
    }

    /**
     * @param int|string|null      $mappingType
     * @param array<string, mixed> $expectedOptions
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

        $this->assertSame($expectedType, $guess->getType());
        $this->assertSame($expectedOptions, $guess->getOptions());
        $this->assertSame($expectedConfidence, $guess->getConfidence());
    }

    /**
     * @phpstan-return iterable<array{int|string|null, string, array<string, mixed>, int}>
     */
    public function guessDataProvider(): iterable
    {
        yield [
            null,
            FieldDescriptionInterface::TYPE_STRING,
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
            FieldDescriptionInterface::TYPE_FLOAT,
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
            FieldDescriptionInterface::TYPE_STRING,
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
            FieldDescriptionInterface::TYPE_ONE_TO_ONE,
            [],
            Guess::HIGH_CONFIDENCE,
        ];

        yield [
            ClassMetadata::ONE_TO_MANY,
            FieldDescriptionInterface::TYPE_ONE_TO_MANY,
            [],
            Guess::HIGH_CONFIDENCE,
        ];
    }
}
