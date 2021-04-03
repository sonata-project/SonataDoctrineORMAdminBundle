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

namespace Sonata\DoctrineORMAdminBundle\Tests\Admin;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Exception\NoValueException;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;

class FieldDescriptionTest extends TestCase
{
    /**
     * NEXT_MAJOR: Remove the group legacy.
     * It's only used because the EmptyFilter is triggering a deprecation in the first test of the test suite.
     *
     * @group legacy
     */
    public function testOptions(): void
    {
        $field = new FieldDescription('name', [
            'template' => 'foo',
            'type' => 'bar',
            'misc' => 'foobar',
        ]);

        // test method shortcut
        $this->assertNull($field->getOption('template'));
        $this->assertNull($field->getOption('type'));

        $this->assertSame('foo', $field->getTemplate());
        $this->assertSame('bar', $field->getType());

        // test the default value option
        $this->assertSame('default', $field->getOption('template', 'default'));

        // test the merge options
        $field->setOption('array', ['key1' => 'val1']);
        $field->mergeOption('array', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOption('non_existent', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOptions(['array' => ['key3' => 'key_3']]);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2', 'key3' => 'key_3'], $field->getOption('array'));

        $field->setOption('integer', 1);

        try {
            $field->mergeOption('integer', []);
            $this->fail('no exception raised !!');
        } catch (\RuntimeException $e) {
        }

        $field->mergeOptions(['final' => 'test']);

        $expected = [
            'misc' => 'foobar',
            'placeholder' => 'short_object_description_placeholder',
            'link_parameters' => [],
            'array' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
                'key3' => 'key_3',
            ],
            'non_existent' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
            ],
            'integer' => 1,
            'final' => 'test',
        ];

        $this->assertSame($expected, $field->getOptions());
    }

    public function testAssociationMapping(): void
    {
        $field = new FieldDescription('name', [], [], [
            'type' => 'integer',
            'fieldName' => 'position',
        ]);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame('position', $field->getFieldName());

        // NEXT_MAJOR: Remove all the rest of the test.
        // cannot overwrite defined definition
        $field->setAssociationMapping([
            'type' => 'overwrite?',
            'fieldName' => 'overwritten',
        ]);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame('overwritten', $field->getFieldName());
    }

    public function testGetParent(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setParent($adminMock);

        $this->assertSame($adminMock, $field->getParent());
    }

    public function testGetAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAdmin());
    }

    public function testGetAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AbstractAdmin::class);
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');
        $field->setAssociationAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAssociationAdmin());
    }

    public function testHasAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AbstractAdmin::class);
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');

        $this->assertFalse($field->hasAssociationAdmin());

        $field->setAssociationAdmin($adminMock);

        $this->assertTrue($field->hasAssociationAdmin());
    }

    public function testGetAssociationMapping(): void
    {
        $associationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('name', [], [], $associationMapping);
        $this->assertSame($associationMapping, $field->getAssociationMapping());
    }

    /**
     * NEXT_MAJOR: Remove this test.
     */
    public function testSetAssociationMappingAllowOnlyForArray(): void
    {
        $this->expectException(\RuntimeException::class);

        $field = new FieldDescription('name');
        $field->setAssociationMapping('test');
    }

    /**
     * NEXT_MAJOR: Remove this test.
     */
    public function testSetFieldMappingAllowOnlyForArray(): void
    {
        $this->expectException(\RuntimeException::class);

        $field = new FieldDescription('name');
        $field->setFieldMapping('test');
    }

    public function testSetFieldMappingSetType(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        $this->assertSame('integer', $field->getType());
    }

    public function testSetFieldMappingSetMappingType(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        $this->assertSame('integer', $field->getMappingType());
    }

    public function testGetTargetModel(): void
    {
        $associationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'targetEntity' => 'someValue',
        ];

        $field = new FieldDescription('position', [], [], $associationMapping);

        $this->assertSame('someValue', $field->getTargetModel());
    }

    public function testIsIdentifierFromFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => true,
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        $this->assertTrue($field->isIdentifier());
    }

    public function testGetFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => 'someId',
        ];

        $field = new FieldDescription('position', [], $fieldMapping);

        $this->assertSame($fieldMapping, $field->getFieldMapping());
    }

    public function testGetValue(): void
    {
        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['myMethod'])->getMock();
        $mockedObject->expects($this->once())->method('myMethod')->willReturn('myMethodValue');

        $field = new FieldDescription('name', ['accessor' => 'myMethod']);

        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueWithParentAssociationMappings(): void
    {
        $mockedSubObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFieldName'])->getMock();
        $mockedSubObject->expects($this->once())->method('getFieldName')->willReturn('value');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getSubObject'])->getMock();
        $mockedObject->expects($this->once())->method('getSubObject')->willReturn($mockedSubObject);

        $field = new FieldDescription('name', [], [], [], [['fieldName' => 'subObject']], 'fieldName');

        $this->assertSame('value', $field->getValue($mockedObject));
    }

    public function testGetValueWhenCannotRetrieve(): void
    {
        $this->expectException(NoValueException::class);

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['myMethod'])->getMock();
        $mockedObject->expects($this->never())->method('myMethod')->willReturn('myMethodValue');

        $field = new FieldDescription('name');

        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForEmbeddedObject(): void
    {
        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedEmbeddedObject->expects($this->once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects($this->once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.myMethod');

        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForMultiLevelEmbeddedObject(): void
    {
        $mockedChildEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyMethod'])->getMock();
        $mockedChildEmbeddedObject->expects($this->once())->method('getMyMethod')->willReturn('myMethodValue');

        $mockedEmbeddedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getChild'])->getMock();
        $mockedEmbeddedObject->expects($this->once())->method('getChild')->willReturn($mockedChildEmbeddedObject);

        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getMyEmbeddedObject'])->getMock();
        $mockedObject->expects($this->once())->method('getMyEmbeddedObject')->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod', [], [], [], [], 'myEmbeddedObject.child.myMethod');

        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    /**
     * @dataProvider getDescribesSingleValuedAssociationProvider
     *
     * @param string|int $mappingType
     */
    public function testDescribesSingleValuedAssociation($mappingType, bool $expected): void
    {
        $fd = new FieldDescription('foo');
        $fd->setAssociationMapping([
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        $this->assertSame($expected, $fd->describesSingleValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array{0: string|int, 1: bool}>
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
    public function testDescribesCollectionValuedAssociation($mappingType, bool $expected)
    {
        $fd = new FieldDescription('foo');
        $fd->setAssociationMapping([
            'fieldName' => 'foo',
            'type' => $mappingType,
        ]);
        $this->assertSame($expected, $fd->describesCollectionValuedAssociation());
    }

    /**
     * @phpstan-return iterable<array{0: string|int, 1: bool}>
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
