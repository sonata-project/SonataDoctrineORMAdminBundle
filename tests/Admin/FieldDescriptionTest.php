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

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Exception\NoValueException;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;

class FieldDescriptionTest extends TestCase
{
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

    public function testSetFieldMapping(): void
    {
        $fieldMapping = ['type' => 'integer'];

        $field = new FieldDescription('position', [], $fieldMapping);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame($fieldMapping, $field->getFieldMapping());
    }

    public function testSetAssociationMapping(): void
    {
        $associationMapping = ['type' => 'integer'];

        $field = new FieldDescription('name', [], [], $associationMapping);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame($associationMapping, $field->getAssociationMapping());
    }

    public function testSetParentAssociationMappings(): void
    {
        $parentAssociationMappings = [['fieldName' => 'subObject']];

        $field = new FieldDescription('name', [], [], [], $parentAssociationMappings);

        $this->assertSame($parentAssociationMappings, $field->getParentAssociationMappings());
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

    public function testGetValue(): void
    {
        $mockedObject = $this->getMockBuilder(\stdClass::class)->addMethods(['getFoo'])->getMock();
        $mockedObject->expects($this->once())->method('getFoo')->willReturn('myMethodValue');

        $field = new FieldDescription('name', ['accessor' => 'foo']);

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
}
