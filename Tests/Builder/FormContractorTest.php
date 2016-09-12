<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\DoctrineORMAdminBundle\Builder\FormContractor;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class FormContractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var FormContractor
     */
    private $formContractor;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->formContractor = new FormContractor($this->formFactory);
    }

    public function testGetFormBuilder()
    {
        $this->formFactory->expects($this->once())->method('createNamedBuilder')
            ->willReturn($this->getMock('Symfony\Component\Form\FormBuilderInterface'));

        $this->assertInstanceOf(
            'Symfony\Component\Form\FormBuilderInterface',
            $this->formContractor->getFormBuilder('test', array('foo' => 'bar'))
        );
    }

    public function testDefaultOptionsForSonataFormTypes()
    {
        $admin = $this->getMock('Sonata\AdminBundle\Admin\AdminInterface');
        $modelManager = $this->getMock('Sonata\AdminBundle\Model\ModelManagerInterface');
        $modelClass = 'FooEntity';

        $admin->method('getModelManager')->willReturn($modelManager);
        $admin->method('getClass')->willReturn($modelClass);

        $fieldDescription = $this->getMock('Sonata\AdminBundle\Admin\FieldDescriptionInterface');
        $fieldDescription->method('getAdmin')->willReturn($admin);
        $fieldDescription->method('getTargetEntity')->willReturn($modelClass);
        $fieldDescription->method('getAssociationAdmin')->willReturn($admin);

        $modelTypes = array(
            'sonata_type_model',
            'sonata_type_model_list',
            'sonata_type_model_hidden',
            'sonata_type_model_autocomplete',
        );
        $adminTypes = array('sonata_type_admin');
        $collectionTypes = array('sonata_type_collection');
        // NEXT_MAJOR: Use only FQCNs when dropping support for Symfony <2.8
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $classTypes = array(
                'Sonata\AdminBundle\Form\Type\ModelType',
                'Sonata\AdminBundle\Form\Type\ModelTypeList',
                'Sonata\AdminBundle\Form\Type\ModelHiddenType',
                'Sonata\AdminBundle\Form\Type\ModelAutocompleteType',
            );

            foreach ($classTypes as $classType) {
                array_push(
                    $modelTypes,
                    // add class type.
                    $classType,
                    // add instance of class type.
                    get_class(
                        $this->getMockBuilder($classType)
                            ->disableOriginalConstructor()
                            ->getMock()
                    )
                );
            }

            $adminTypes[] = 'Sonata\AdminBundle\Form\Type\AdminType';
            $collectionTypes[] = 'Sonata\CoreBundle\Form\Type\CollectionType';
        }

        // model types
        foreach ($modelTypes as $formType) {
            $options = $this->formContractor->getDefaultOptions($formType, $fieldDescription);
            $this->assertSame($fieldDescription, $options['sonata_field_description']);
            $this->assertSame($modelClass, $options['class']);
            $this->assertSame($modelManager, $options['model_manager']);
        }

        // admin type
        $fieldDescription->method('getMappingType')->willReturn(ClassMetadata::ONE_TO_ONE);
        foreach ($adminTypes as $formType) {
            $options = $this->formContractor->getDefaultOptions($formType, $fieldDescription);
            $this->assertSame($fieldDescription, $options['sonata_field_description']);
            $this->assertSame($modelClass, $options['data_class']);
            $this->assertSame(false, $options['btn_add']);
            $this->assertSame(false, $options['delete']);
        }

        // collection type
        $fieldDescription->method('getMappingType')->willReturn(ClassMetadata::ONE_TO_MANY);
        foreach ($collectionTypes as $formType) {
            $options = $this->formContractor->getDefaultOptions($formType, $fieldDescription);
            $this->assertSame($fieldDescription, $options['sonata_field_description']);
            $this->assertSame('sonata_type_admin', $options['type']);
            $this->assertSame(true, $options['modifiable']);
            $this->assertSame($fieldDescription, $options['type_options']['sonata_field_description']);
            $this->assertSame($modelClass, $options['type_options']['data_class']);
        }
    }

    public function testAdminClassAttachForNotMappedField()
    {
        // Given
        $modelManager = $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Model\ModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $modelManager->method('hasMetadata')->willReturn(false);

        $admin = $this->getMock('Sonata\AdminBundle\Admin\AdminInterface');
        $admin->method('getModelManager')->willReturn($modelManager);

        $fieldDescription = $this->getMock('Sonata\AdminBundle\Admin\FieldDescriptionInterface');
        $fieldDescription->method('getMappingType')->willReturn('simple');
        $fieldDescription->method('getType')->willReturn('sonata_type_model_list');
        $fieldDescription->method('getOption')->with($this->logicalOr(
            $this->equalTo('edit'),
            $this->equalTo('admin_code')
        ))->willReturn('sonata.admin.code');

        // Then
        $admin
            ->expects($this->once())
            ->method('attachAdminClass')
            ->with($fieldDescription)
        ;

        // When
        $this->formContractor->fixFieldDescription($admin, $fieldDescription);
    }
}
