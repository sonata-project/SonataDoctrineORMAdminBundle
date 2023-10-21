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
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Exception\NoValueException;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\DoctrineORMAdminBundle\FieldDescription\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Enum\Suit;

final class FieldDescriptionTest extends TestCase
{
    public function testOptions(): void
    {
        $field = new FieldDescription('name', [
            'template' => 'foo',
            'type' => 'bar',
            'misc' => 'foobar',
        ]);

        // test method shortcut
        static::assertNull($field->getOption('template'));
        static::assertNull($field->getOption('type'));

        static::assertSame('foo', $field->getTemplate());
        static::assertSame('bar', $field->getType());

        // test the default value option
        static::assertSame('default', $field->getOption('template', 'default'));

        // test the merge options
        $field->setOption('array', ['key1' => 'val1']);
        $field->mergeOption('array', ['key1' => 'key_1', 'key2' => 'key_2']);

        static::assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOption('non_existent', ['key1' => 'key_1', 'key2' => 'key_2']);

        static::assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->setOption('integer', 1);

        try {
            $field->mergeOption('integer', []);
            static::fail('no exception raised !!');
        } catch (\RuntimeException) {
        }

        $expected = [
            'misc' => 'foobar',
            'array' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
            ],
            'non_existent' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
            ],
            'integer' => 1,
        ];

        static::assertSame($expected, $field->getOptions());
    }

    public function testGetParent(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setParent($adminMock);

        static::assertSame($adminMock, $field->getParent());
    }

    public function testGetAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setAdmin($adminMock);

        static::assertSame($adminMock, $field->getAdmin());
    }

    public function testGetAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $adminMock->expects(static::once())
            ->method('setParentFieldDescription')
            ->with(static::isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');
        $field->setAssociationAdmin($adminMock);

        static::assertSame($adminMock, $field->getAssociationAdmin());
    }

    public function testHasAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $adminMock->expects(static::once())
            ->method('setParentFieldDescription')
            ->with(static::isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');

        static::assertFalse($field->hasAssociationAdmin());

        $field->setAssociationAdmin($adminMock);

        static::assertTrue($field->hasAssociationAdmin());
    }

    public function testSetFieldMapping(): void
    {
        $fieldMapping = ['type' => 'integer'];

        $field = new FieldDescription('position', [], $fieldMapping);

        static::assertSame('integer', $field->getType());
        static::assertSame('integer', $field->getMappingType());
        static::assertSame($fieldMapping, $field->getFieldMapping());
    }

    public function testSetAssociationMapping(): void
    {
        $associationMapping = ['type' => 'integer'];

        $field = new FieldDescription('name', [], [], $associationMapping);

        static::assertSame('integer', $field->getType());
        static::assertSame('integer', $field->getMappingType());
        static::assertSame($associationMapping, $field->getAssociationMapping());
    }

    public function testSetParentAssociationMappings(): void
    {
        $parentAssociationMappings = [['fieldName' => 'subObject']];

        $field = new FieldDescription('name', [], [], [], $parentAssociationMappings);

        static::assertSame($parentAssociationMappings, $field->getParentAssociationMappings());
    }

    public function testSetInvalidParentAssociationMappings(): void
    {
        $parentAssociationMappings = ['subObject'];

        $this->expectException(\InvalidArgumentException::class);
        new FieldDescription('name', [], [], [], $parentAssociationMappings);
    }

    public function testGetTargetModel(): void
    {
        $associationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'targetEntity' => \stdClass::class,
        ];

        $field = new FieldDescription('position', [], [], $associationMapping);

        static::assertSame(\stdClass::class, $field->getTargetModel());
    }

    public function testIsIdentifierFromFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => true,
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        static::assertTrue($field->isIdentifier());
    }

    public function testGetValue(): void
    {
        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFoo'])->getMock();
        $mockedObject->expects(static::once())->method('getFoo')->willReturn('myMethodValue');

        $field = new FieldDescription('name', ['accessor' => 'foo']);

        static::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueWithParentAssociationMappings(): void
    {
        $mockedSubObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFieldName'])->getMock();
        $mockedSubObject->expects(static::once())->method('getFieldName')->willReturn('value');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getSubObject'])->getMock();
        $mockedObject->expects(static::once())->method('getSubObject')->willReturn($mockedSubObject);

        $field = new FieldDescription('name', [], [], [], [['fieldName' => 'subObject']], 'fieldName');

        static::assertSame('value', $field->getValue($mockedObject));
    }

    public function testGetValueWhenCannotRetrieve(): void
    {
        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['myMethod'])->getMock();
        $mockedObject->expects(static::never())->method('myMethod')->willReturn('myMethodValue');

        $admin = $this->createStub(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setAdmin($admin);

        $this->expectException(NoValueException::class);
        static::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForEmbeddedObject(): void
    {
        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedEmbeddedObject->expects(static::once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects(static::once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.myMethod');

        static::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForMultiLevelEmbeddedObject(): void
    {
        $mockedChildEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedChildEmbeddedObject->expects(static::once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getChild'])->getMock();
        $mockedEmbeddedObject->expects(static::once())->method('getChild')->willReturn($mockedChildEmbeddedObject);

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects(static::once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.child.myMethod');

        static::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testEnum(): void
    {
        $fieldMapping = ['type' => 'string', 'enumType' => Suit::class];

        $field = new FieldDescription('bar', [], $fieldMapping);

        static::assertSame('string', $field->getType());
        static::assertSame('enum', $field->getMappingType());
        static::assertSame($fieldMapping, $field->getFieldMapping());
    }

    /**
     * @dataProvider provideDescribesSingleValuedAssociationCases
     */
    public function testDescribesSingleValuedAssociation(string|int $mappingType, bool $expected): void
    {
        $fd = new FieldDescription('foo', [], [], [
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        static::assertSame($expected, $fd->describesSingleValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array-key, array{0: string|int, 1: bool}>
     */
    public function provideDescribesSingleValuedAssociationCases(): iterable
    {
        yield 'one to one' => [ClassMetadata::ONE_TO_ONE, true];
        yield 'many to one' => [ClassMetadata::MANY_TO_ONE, true];
        yield 'one to many' => [ClassMetadata::ONE_TO_MANY, false];
        yield 'many to many' => [ClassMetadata::MANY_TO_MANY, false];
        yield 'string' => ['string', false];
    }

    /**
     * @dataProvider provideDescribesCollectionValuedAssociationCases
     */
    public function testDescribesCollectionValuedAssociation(string|int $mappingType, bool $expected): void
    {
        $fd = new FieldDescription('foo', [], [], [
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        static::assertSame($expected, $fd->describesCollectionValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array-key, array{0: string|int, 1: bool}>
     */
    public function provideDescribesCollectionValuedAssociationCases(): iterable
    {
        yield 'one to one' => [ClassMetadata::ONE_TO_ONE, false];
        yield 'many to one' => [ClassMetadata::MANY_TO_ONE, false];
        yield 'one to many' => [ClassMetadata::ONE_TO_MANY, true];
        yield 'many to many' => [ClassMetadata::MANY_TO_MANY, true];
        yield 'string' => ['string', false];
    }
}
