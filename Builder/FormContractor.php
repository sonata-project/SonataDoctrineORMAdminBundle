<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Builder;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\FormContractorInterface;
use Symfony\Component\Form\FormFactoryInterface;

class FormContractor implements FormContractorInterface
{
    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated since version 3.0.4, to be removed in 4.0
     *
     * @var FormFactoryInterface
     */
    protected $fieldFactory;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
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
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                get_class($admin)
            ));
        }

        $fieldDescription->setAdmin($admin);
        $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'standard'));

        if ($this->hasAssociation($fieldDescription) || $fieldDescription->getOption('admin_code')) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    /**
     * @return FormFactoryInterface
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormBuilder($name, array $options = [])
    {
        return $this->getFormFactory()->createNamedBuilder(
            $name,
            'Symfony\Component\Form\Extension\Core\Type\FormType',
            null,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions($type, FieldDescriptionInterface $fieldDescription)
    {
        $options = [];
        $options['sonata_field_description'] = $fieldDescription;

        if ($this->checkFormClass($type, [
            'Sonata\AdminBundle\Form\Type\ModelType',
            'Sonata\AdminBundle\Form\Type\ModelTypeList',
            'Sonata\AdminBundle\Form\Type\ModelListType',
            'Sonata\AdminBundle\Form\Type\ModelHiddenType',
            'Sonata\AdminBundle\Form\Type\ModelAutocompleteType',
        ])) {
            if ($fieldDescription->getOption('edit') === 'list') {
                throw new \LogicException(
                    'The `sonata_type_model` type does not accept an `edit` option anymore,'
                    .' please review the UPGRADE-2.1.md file from the SonataAdminBundle'
                );
            }

            $options['class'] = $fieldDescription->getTargetEntity();
            $options['model_manager'] = $fieldDescription->getAdmin()->getModelManager();

            if ($this->checkFormClass($type, ['Sonata\AdminBundle\Form\Type\ModelAutocompleteType'])) {
                if (!$fieldDescription->getAssociationAdmin()) {
                    throw new \RuntimeException(sprintf(
                        'The current field `%s` is not linked to an admin.'
                        .' Please create one for the target entity: `%s`',
                        $fieldDescription->getName(),
                        $fieldDescription->getTargetEntity()
                    ));
                }
            }
        } elseif ($this->checkFormClass($type, ['Sonata\AdminBundle\Form\Type\AdminType'])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetEntity()
                ));
            }

            if (!in_array($fieldDescription->getMappingType(), [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                throw new \RuntimeException(sprintf(
                    'You are trying to add `sonata_type_admin` field `%s` which is not One-To-One or  Many-To-One.'
                    .' Maybe you want `sonata_type_collection` instead?',
                    $fieldDescription->getName()
                ));
            }

            // set sensitive default value to have a component working fine out of the box
            $options['btn_add'] = false;
            $options['delete'] = false;

            $options['data_class'] = $fieldDescription->getAssociationAdmin()->getClass();
            $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'admin'));
        } elseif ($this->checkFormClass($type, ['Sonata\CoreBundle\Form\Type\CollectionType'])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetEntity()
                ));
            }

            $options['type'] = 'sonata_type_admin';
            $options['modifiable'] = true;
            $options['type_options'] = [
                'sonata_field_description' => $fieldDescription,
                'data_class' => $fieldDescription->getAssociationAdmin()->getClass(),
            ];
        }

        return $options;
    }

    /**
     * @param FieldDescriptionInterface $fieldDescription
     *
     * @return bool
     */
    private function hasAssociation(FieldDescriptionInterface $fieldDescription)
    {
        return in_array($fieldDescription->getMappingType(), [
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
            ClassMetadataInfo::MANY_TO_ONE,
            ClassMetadataInfo::ONE_TO_ONE,
        ]);
    }

    /**
     * @param string $type
     * @param array  $classes
     *
     * @return array
     */
    private function checkFormClass($type, $classes)
    {
        return array_filter($classes, function ($subclass) use ($type) {
            return is_a($type, $subclass, true);
        });
    }
}
