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
        self::assertNull($field->getOption('template'));
        self::assertNull($field->getOption('type'));

        self::assertSame('foo', $field->getTemplate());
        self::assertSame('bar', $field->getType());

        // test the default value option
        self::assertSame('default', $field->getOption('template', 'default'));

        // test the merge options
        $field->setOption('array', ['key1' => 'val1']);
        $field->mergeOption('array', ['key1' => 'key_1', 'key2' => 'key_2']);

        self::assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOption('non_existent', ['key1' => 'key_1', 'key2' => 'key_2']);

        self::assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->setOption('integer', 1);

        try {
            $field->mergeOption('integer', []);
            self::fail('no exception raised !!');
        } catch (\RuntimeException $e) {
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

        self::assertSame($expected, $field->getOptions());
    }

    public function testGetParent(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setParent($adminMock);

        self::assertSame($adminMock, $field->getParent());
    }

    public function testGetAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setAdmin($adminMock);

        self::assertSame($adminMock, $field->getAdmin());
    }

    public function testGetAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $adminMock->expects(self::once())
            ->method('setParentFieldDescription')
            ->with(self::isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');
        $field->setAssociationAdmin($adminMock);

        self::assertSame($adminMock, $field->getAssociationAdmin());
    }

    public function testHasAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $adminMock->expects(self::once())
            ->method('setParentFieldDescription')
            ->with(self::isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');

        self::assertFalse($field->hasAssociationAdmin());

        $field->setAssociationAdmin($adminMock);

        self::assertTrue($field->hasAssociationAdmin());
    }

    public function testSetFieldMapping(): void
    {
        $fieldMapping = ['type' => 'integer'];

        $field = new FieldDescription('position', [], $fieldMapping);

        self::assertSame('integer', $field->getType());
        self::assertSame('integer', $field->getMappingType());
        self::assertSame($fieldMapping, $field->getFieldMapping());
    }

    public function testSetAssociationMapping(): void
    {
        $associationMapping = ['type' => 'integer'];

        $field = new FieldDescription('name', [], [], $associationMapping);

        self::assertSame('integer', $field->getType());
        self::assertSame('integer', $field->getMappingType());
        self::assertSame($associationMapping, $field->getAssociationMapping());
    }

    public function testSetParentAssociationMappings(): void
    {
        $parentAssociationMappings = [['fieldName' => 'subObject']];

        $field = new FieldDescription('name', [], [], [], $parentAssociationMappings);

        self::assertSame($parentAssociationMappings, $field->getParentAssociationMappings());
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

        self::assertSame(\stdClass::class, $field->getTargetModel());
    }

    public function testIsIdentifierFromFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => true,
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        self::assertTrue($field->isIdentifier());
    }

    public function testGetValue(): void
    {
        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFoo'])->getMock();
        $mockedObject->expects(self::once())->method('getFoo')->willReturn('myMethodValue');

        $field = new FieldDescription('name', ['accessor' => 'foo']);

        self::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueWithParentAssociationMappings(): void
    {
        $mockedSubObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFieldName'])->getMock();
        $mockedSubObject->expects(self::once())->method('getFieldName')->willReturn('value');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getSubObject'])->getMock();
        $mockedObject->expects(self::once())->method('getSubObject')->willReturn($mockedSubObject);

        $field = new FieldDescription('name', [], [], [], [['fieldName' => 'subObject']], 'fieldName');

        self::assertSame('value', $field->getValue($mockedObject));
    }

    public function testGetValueWhenCannotRetrieve(): void
    {
        $this->expectException(NoValueException::class);

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['myMethod'])->getMock();
        $mockedObject->expects(self::never())->method('myMethod')->willReturn('myMethodValue');

        $field = new FieldDescription('name');

        self::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForEmbeddedObject(): void
    {
        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedEmbeddedObject->expects(self::once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects(self::once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.myMethod');

        self::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForMultiLevelEmbeddedObject(): void
    {
        $mockedChildEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedChildEmbeddedObject->expects(self::once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getChild'])->getMock();
        $mockedEmbeddedObject->expects(self::once())->method('getChild')->willReturn($mockedChildEmbeddedObject);

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects(self::once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.child.myMethod');

        self::assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    /**
     * @dataProvider getDescribesSingleValuedAssociationProvider
     *
     * @param string|int $mappingType
     */
    public function testDescribesSingleValuedAssociation($mappingType, bool $expected): void
    {
        $fd = new FieldDescription('foo', [], [], [
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        self::assertSame($expected, $fd->describesSingleValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array-key, array{0: string|int, 1: bool}>
     */
    public function getDescribesSingleValuedAssociationProvider(): iterable
    {
        yield 'one to one' => [ClassMetadata::ONE_TO_ONE, true];
        yield 'many to one' => [ClassMetadata::MANY_TO_ONE, true];
        yield 'one to many' => [ClassMetadata::ONE_TO_MANY, false];
        yield 'many to many' => [ClassMetadata::MANY_TO_MANY, false];
        yield 'string' => ['string', false];
    }

    /**
     * @dataProvider getDescribesCollectionValuedAssociationProvider
     *
     * @param string|int $mappingType
     */
    public function testDescribesCollectionValuedAssociation($mappingType, bool $expected): void
    {
        $fd = new FieldDescription('foo', [], [], [
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        self::assertSame($expected, $fd->describesCollectionValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array-key, array{0: string|int, 1: bool}>
     */
    public function getDescribesCollectionValuedAssociationProvider(): iterable
    {
        yield 'one to one' => [ClassMetadata::ONE_TO_ONE, false];
        yield 'many to one' => [ClassMetadata::MANY_TO_ONE, false];
        yield 'one to many' => [ClassMetadata::ONE_TO_MANY, true];
        yield 'many to many' => [ClassMetadata::MANY_TO_MANY, true];
        yield 'string' => ['string', false];
    }
}
