<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Builder;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\FormContractorInterface;

use Symfony\Component\Form\FormFactoryInterface;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;

class FormContractor implements FormContractorInterface
{
    protected $fieldFactory;

    /**
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * The method defines the correct default settings for the provided FieldDescription
     *
     * @param \Sonata\AdminBundle\Admin\AdminInterface            $admin
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     *
     * @return void
     */
    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        if ($admin->getModelManager()->hasMetadata($admin->getClass())) {
            $metadata = $admin->getModelManager()->getMetadata($admin->getClass());

            // set the default field mapping
            if (isset($metadata->fieldMappings[$fieldDescription->getName()])) {
                $fieldDescription->setFieldMapping($metadata->fieldMappings[$fieldDescription->getName()]);
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$fieldDescription->getName()])) {
                $fieldDescription->setAssociationMapping($metadata->associationMappings[$fieldDescription->getName()]);
            }
        }

        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf('Please define a type for field `%s` in `%s`', $fieldDescription->getName(), get_class($admin)));
        }

        $fieldDescription->setAdmin($admin);
        $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'standard'));

        if (in_array($fieldDescription->getMappingType(), array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::MANY_TO_ONE, ClassMetadataInfo::ONE_TO_ONE ))) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    /**
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return \Symfony\Component\Form\FormBuilder
     */
    public function getFormBuilder($name, array $options = array())
    {
        return $this->getFormFactory()->createNamedBuilder('form', $name, null, $options);
    }

    /**
     * @param string                                              $type
     * @param \Sonata\AdminBundle\Admin\FieldDescriptionInterface $fieldDescription
     *
     * @return array
     */
    public function getDefaultOptions($type, FieldDescriptionInterface $fieldDescription)
    {
        $options                             = array();
        $options['sonata_field_description'] = $fieldDescription;

        if ($type == 'sonata_type_model') {
            $options['class']         = $fieldDescription->getTargetEntity();
            $options['model_manager'] = $fieldDescription->getAdmin()->getModelManager();

            switch ($fieldDescription->getMappingType()) {
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_MANY:
                    $options['multiple'] = true;
                    $options['parent']   = 'choice';
                    break;

                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::ONE_TO_ONE:
                    break;
            }

            if ($fieldDescription->getOption('edit') == 'list') {
                $options['parent'] = 'text';

                if (!array_key_exists('required', $options)) {
                    $options['required'] = false;
                }
            }

        } else if ($type == 'sonata_type_admin') {

            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf('The current field `%s` is not linked to an admin. Please create one for the target entity : `%s`', $fieldDescription->getName(), $fieldDescription->getTargetEntity()));
            }

            $options['data_class']=$fieldDescription->getAssociationAdmin()->getClass();
            $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'admin'));
        } else if ($type == 'sonata_type_collection') {

            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf('The current field `%s` is not linked to an admin. Please create one for the target entity : `%s`', $fieldDescription->getName(), $fieldDescription->getTargetEntity()));
            }

            $options['type']         = 'sonata_type_admin';
            $options['modifiable']   = true;
            $options['type_options'] = array(
                'sonata_field_description' => $fieldDescription,
                'data_class'               => $fieldDescription->getAssociationAdmin()->getClass()
            );

        }

        return $options;
    }
}