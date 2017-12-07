<?php

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
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;

class FieldDescriptionTest extends TestCase
{
    public function testOptions()
    {
        $field = new FieldDescription();
        $field->setOptions([
            'template' => 'foo',
            'type' => 'bar',
            'misc' => 'foobar',
        ]);

        // test method shortcut
        $this->assertNull($field->getOption('template'));
        $this->assertNull($field->getOption('type'));

        $this->assertEquals('foo', $field->getTemplate());
        $this->assertEquals('bar', $field->getType());

        // test the default value option
        $this->assertEquals('default', $field->getOption('template', 'default'));

        // test the merge options
        $field->setOption('array', ['key1' => 'val1']);
        $field->mergeOption('array', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertEquals(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOption('non_existant', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertEquals(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOptions(['array' => ['key3' => 'key_3']]);

        $this->assertEquals(['key1' => 'key_1', 'key2' => 'key_2', 'key3' => 'key_3'], $field->getOption('array'));

        $field->setOption('integer', 1);

        try {
            $field->mergeOption('integer', []);
            $this->fail('no exception raised !!');
        } catch (\RuntimeException $e) {
        }

        $field->mergeOptions(['final' => 'test']);

        $expected = [
            'misc' => 'foobar',
            'array' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
                'key3' => 'key_3',
            ],
            'non_existant' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
            ],
            'integer' => 1,
            'final' => 'test',
            'placeholder' => 'short_object_description_placeholder',
            'link_parameters' => [],
        ];

        $this->assertEquals($expected, $field->getOptions());
    }

    public function testAssociationMapping()
    {
        $field = new FieldDescription();
        $field->setAssociationMapping([
            'type' => 'integer',
            'fieldName' => 'position',
        ]);

        $this->assertEquals('integer', $field->getType());
        $this->assertEquals('integer', $field->getMappingType());
        $this->assertEquals('position', $field->getFieldName());

        // cannot overwrite defined definition
        $field->setAssociationMapping([
            'type' => 'overwrite?',
            'fieldName' => 'overwritten',
        ]);

        $this->assertEquals('integer', $field->getType());
        $this->assertEquals('integer', $field->getMappingType());
        $this->assertEquals('overwritten', $field->getFieldName());

        $field->setMappingType('string');
        $this->assertEquals('string', $field->getMappingType());
        $this->assertEquals('integer', $field->getType());
    }

    public function testSetName()
    {
        $field = new FieldDescription();
        $field->setName('New field description name');

        $this->assertEquals($field->getName(), 'New field description name');
    }

    public function testSetNameSetFieldNameToo()
    {
        $field = new FieldDescription();
        $field->setName('New field description name');

        $this->assertEquals($field->getFieldName(), 'New field description name');
    }

    public function testSetNameDoesNotSetFieldNameWhenSetBefore()
    {
        $field = new FieldDescription();
        $field->setFieldName('field name');
        $field->setName('New field description name');

        $this->assertEquals($field->getFieldName(), 'field name');
    }

    public function testGetParent()
    {
        $adminMock = $this->createMock('Sonata\AdminBundle\Admin\AdminInterface');
        $field = new FieldDescription();
        $field->setParent($adminMock);

        $this->assertSame($adminMock, $field->getParent());
    }

    public function testGetHelp()
    {
        $field = new FieldDescription();
        $field->setHelp('help message');

        $this->assertEquals($field->getHelp(), 'help message');
    }

    public function testGetAdmin()
    {
        $adminMock = $this->createMock('Sonata\AdminBundle\Admin\AdminInterface');
        $field = new FieldDescription();
        $field->setAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAdmin());
    }

    public function testGetAssociationAdmin()
    {
        $adminMock = $this->createMock('Sonata\AdminBundle\Admin\AbstractAdmin');
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf('Sonata\AdminBundle\Admin\FieldDescriptionInterface'));

        $field = new FieldDescription();
        $field->setAssociationAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAssociationAdmin());
    }

    public function testHasAssociationAdmin()
    {
        $adminMock = $this->createMock('Sonata\AdminBundle\Admin\AbstractAdmin');
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf('Sonata\AdminBundle\Admin\FieldDescriptionInterface'));

        $field = new FieldDescription();

        $this->assertFalse($field->hasAssociationAdmin());

        $field->setAssociationAdmin($adminMock);

        $this->assertTrue($field->hasAssociationAdmin());
    }

    public function testGetValue()
    {
        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('myMethod')
            ->will($this->returnValue('myMethodValue'));

        $field = new FieldDescription();
        $field->setOption('code', 'myMethod');

        $this->assertEquals($field->getValue($mockedObject), 'myMethodValue');
    }

    public function testGetValueWhenCannotRetrieve()
    {
        $this->expectException(\Sonata\AdminBundle\Exception\NoValueException::class);

        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedObject->expects($this->never())
            ->method('myMethod')
            ->will($this->returnValue('myMethodValue'));

        $field = new FieldDescription();

        $this->assertEquals($field->getValue($mockedObject), 'myMethodValue');
    }

    public function testGetAssociationMapping()
    {
        $assocationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription();
        $field->setAssociationMapping($assocationMapping);

        $this->assertEquals($assocationMapping, $field->getAssociationMapping());
    }

    public function testSetAssociationMappingAllowOnlyForArray()
    {
        $this->expectException(\RuntimeException::class);

        $field = new FieldDescription();
        $field->setAssociationMapping('test');
    }

    public function testSetFieldMappingAllowOnlyForArray()
    {
        $this->expectException(\RuntimeException::class);

        $field = new FieldDescription();
        $field->setFieldMapping('test');
    }

    public function testSetFieldMappingSetType()
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription();
        $field->setFieldMapping($fieldMapping);

        $this->assertEquals('integer', $field->getType());
    }

    public function testSetFieldMappingSetMappingType()
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription();
        $field->setFieldMapping($fieldMapping);

        $this->assertEquals('integer', $field->getMappingType());
    }

    public function testSetFieldMappingSetFieldName()
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription();
        $field->setFieldMapping($fieldMapping);

        $this->assertEquals('position', $field->getFieldName());
    }

    public function testGetTargetEntity()
    {
        $assocationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'targetEntity' => 'someValue',
        ];

        $field = new FieldDescription();

        $this->assertNull($field->getTargetEntity());

        $field->setAssociationMapping($assocationMapping);

        $this->assertEquals('someValue', $field->getTargetEntity());
    }

    public function testIsIdentifierFromFieldMapping()
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => 'someId',
        ];

        $field = new FieldDescription();
        $field->setFieldMapping($fieldMapping);

        $this->assertEquals('someId', $field->isIdentifier());
    }

    public function testGetFieldMapping()
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => 'someId',
        ];

        $field = new FieldDescription();
        $field->setFieldMapping($fieldMapping);

        $this->assertEquals($fieldMapping, $field->getFieldMapping());
    }

    public function testGetValueForEmbeddedObject()
    {
        $mockedEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedEmbeddedObject->expects($this->once())
                    ->method('myMethod')
                    ->will($this->returnValue('myMethodValue'));

        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getMyEmbeddedObject'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('getMyEmbeddedObject')
            ->will($this->returnValue($mockedEmbeddedObject));

        $field = new FieldDescription();
        $field->setFieldMapping([
            'declaredField' => 'myEmbeddedObject', 'type' => 'string', 'fieldName' => 'myEmbeddedObject.myMethod',
        ]);
        $field->setFieldName('myMethod');
        $field->setOption('code', 'myMethod');

        $this->assertEquals('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForMultiLevelEmbeddedObject()
    {
        $mockedChildEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedChildEmbeddedObject->expects($this->once())
            ->method('myMethod')
            ->will($this->returnValue('myMethodValue'));
        $mockedEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getChild'])
            ->getMock();
        $mockedEmbeddedObject->expects($this->once())
            ->method('getChild')
            ->will($this->returnValue($mockedChildEmbeddedObject));
        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getMyEmbeddedObject'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('getMyEmbeddedObject')
            ->will($this->returnValue($mockedEmbeddedObject));
        $field = new FieldDescription();
        $field->setFieldMapping([
            'declaredField' => 'myEmbeddedObject.child', 'type' => 'string', 'fieldName' => 'myMethod',
        ]);
        $field->setFieldName('myMethod');
        $field->setOption('code', 'myMethod');
        $this->assertEquals('myMethodValue', $field->getValue($mockedObject));
    }
}
