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

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\FormContractorInterface;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Form\Type\ModelTypeList;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

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

    public function getFormBuilder($name, array $options = [])
    {
        return $this->getFormFactory()->createNamedBuilder($name, FormType::class, null, $options);
    }

    public function getDefaultOptions($type, FieldDescriptionInterface $fieldDescription)
    {
        $options = [];
        $options['sonata_field_description'] = $fieldDescription;

        // NEXT_MAJOR: Check only against FQCNs when dropping support for Symfony 2.8
        if (in_array($type, [
            'sonata_type_model',
            'sonata_type_model_list',
            'sonata_type_model_hidden',
            'sonata_type_model_autocomplete',
        ], true) || $this->checkFormClass($type, [
            ModelType::class,
            ModelTypeList::class,
            ModelListType::class,
            ModelHiddenType::class,
            ModelAutocompleteType::class,
        ])) {
            if ('list' === $fieldDescription->getOption('edit')) {
                throw new \LogicException(
                    'The `sonata_type_model` type does not accept an `edit` option anymore,'
                    .' please review the UPGRADE-2.1.md file from the SonataAdminBundle'
                );
            }

            $options['class'] = $fieldDescription->getTargetEntity();
            $options['model_manager'] = $fieldDescription->getAdmin()->getModelManager();

            // NEXT_MAJOR: Check only against FQCNs when dropping support for Symfony 2.8
            if ('sonata_type_model_autocomplete' === $type || $this->checkFormClass($type, [ModelAutocompleteType::class])) {
                if (!$fieldDescription->getAssociationAdmin()) {
                    throw new \RuntimeException(sprintf(
                        'The current field `%s` is not linked to an admin.'
                        .' Please create one for the target entity: `%s`',
                        $fieldDescription->getName(),
                        $fieldDescription->getTargetEntity()
                    ));
                }
            }
            // NEXT_MAJOR: Check only against FQCNs when dropping support for Symfony 2.8
        } elseif ('sonata_type_admin' === $type || $this->checkFormClass($type, [AdminType::class])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetEntity()
                ));
            }

            if (!in_array($fieldDescription->getMappingType(), [
                ClassMetadata::ONE_TO_ONE,
                ClassMetadata::MANY_TO_ONE,
            ])) {
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
            $options['empty_data'] = function () use ($fieldDescription) {
                return $fieldDescription->getAssociationAdmin()->getNewInstance();
            };
            $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'admin'));
        // NEXT_MAJOR: Check only against FQCNs when dropping support for Symfony 2.8
        } elseif ('sonata_type_collection' === $type || $this->checkFormClass($type, [CollectionType::class])) {
            if (!$fieldDescription->getAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetEntity()
                ));
            }

            $options['type'] = AdminType::class;
            $options['modifiable'] = true;
            $options['type_options'] = [
                'sonata_field_description' => $fieldDescription,
                'data_class' => $fieldDescription->getAssociationAdmin()->getClass(),
                'empty_data' => function () use ($fieldDescription) {
                    return $fieldDescription->getAssociationAdmin()->getNewInstance();
                },
            ];
        }

        return $options;
    }

    /**
     * @return bool
     */
    private function hasAssociation(FieldDescriptionInterface $fieldDescription)
    {
        return in_array($fieldDescription->getMappingType(), [
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
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
