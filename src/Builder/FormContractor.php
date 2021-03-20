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

namespace Sonata\DoctrineORMAdminBundle\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Builder\FormContractorInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelHiddenType;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

final class FormContractor implements FormContractorInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                \get_class($fieldDescription->getAdmin())
            ));
        }

        $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'standard'));

        if ($this->hasAssociation($fieldDescription) || $fieldDescription->getOption('admin_code')) {
            $fieldDescription->getAdmin()->attachAdminClass($fieldDescription);
        }
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->formFactory;
    }

    public function getFormBuilder(string $name, array $formOptions = []): FormBuilderInterface
    {
        return $this->getFormFactory()->createNamedBuilder($name, FormType::class, null, $formOptions);
    }

    public function getDefaultOptions(?string $type, FieldDescriptionInterface $fieldDescription, array $formOptions = []): array
    {
        $options = [];
        $options['sonata_field_description'] = $fieldDescription;

        if ($this->isAnyInstanceOf($type, [
            ModelType::class,
            ModelListType::class,
            ModelHiddenType::class,
            ModelAutocompleteType::class,
        ])) {
            if ('list' === $fieldDescription->getOption('edit')) {
                throw new \LogicException(sprintf(
                    'The `%s` type does not accept an `edit` option anymore,'
                    .' please review the UPGRADE-2.1.md file from the SonataAdminBundle',
                    ModelType::class
                ));
            }

            $options['class'] = $fieldDescription->getTargetModel();
            $options['model_manager'] = $fieldDescription->getAdmin()->getModelManager();

            if ($this->isAnyInstanceOf($type, [ModelAutocompleteType::class])) {
                if (!$fieldDescription->hasAssociationAdmin()) {
                    throw new \RuntimeException(sprintf(
                        'The current field `%s` is not linked to an admin.'
                        .' Please create one for the target entity: `%s`',
                        $fieldDescription->getName(),
                        $fieldDescription->getTargetModel()
                    ));
                }
            }
        } elseif ($this->isAnyInstanceOf($type, [AdminType::class])) {
            if (!$fieldDescription->hasAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetModel()
                ));
            }

            if (!\in_array($fieldDescription->getMappingType(), [
                ClassMetadata::ONE_TO_ONE,
                ClassMetadata::MANY_TO_ONE,
            ], true)) {
                throw new \RuntimeException(sprintf(
                    'You are trying to add `%s` field `%s` which is not One-To-One or  Many-To-One.'
                    .' Maybe you want `%s` instead?',
                    AdminType::class,
                    $fieldDescription->getName(),
                    CollectionType::class
                ));
            }

            // set sensitive default value to have a component working fine out of the box
            $options['btn_add'] = false;
            $options['delete'] = false;

            $options['data_class'] = $fieldDescription->getAssociationAdmin()->getClass();
            $options['empty_data'] = static function () use ($fieldDescription) {
                return $fieldDescription->getAssociationAdmin()->getNewInstance();
            };
            $fieldDescription->setOption('edit', $fieldDescription->getOption('edit', 'admin'));
        } elseif ($this->isAnyInstanceOf($type, [CollectionType::class])) {
            if (!$fieldDescription->hasAssociationAdmin()) {
                throw new \RuntimeException(sprintf(
                    'The current field `%s` is not linked to an admin.'
                    .' Please create one for the target entity : `%s`',
                    $fieldDescription->getName(),
                    $fieldDescription->getTargetModel()
                ));
            }

            $options['type'] = AdminType::class;
            $options['modifiable'] = true;
            $options['type_options'] = $this->getDefaultAdminTypeOptions($fieldDescription, $formOptions);
        }

        return $options;
    }

    private function hasAssociation(FieldDescriptionInterface $fieldDescription): bool
    {
        return \in_array($fieldDescription->getMappingType(), [
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
        ], true);
    }

    /**
     * @param string[] $classes
     *
     * @phpstan-param class-string[] $classes
     */
    private function isAnyInstanceOf(?string $type, array $classes): bool
    {
        if (null === $type) {
            return false;
        }

        foreach ($classes as $class) {
            if (is_a($type, $class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $formOptions
     *
     * @return array<string, mixed>
     */
    private function getDefaultAdminTypeOptions(FieldDescriptionInterface $fieldDescription, array $formOptions): array
    {
        $typeOptions = [
            'sonata_field_description' => $fieldDescription,
            'data_class' => $fieldDescription->getAssociationAdmin()->getClass(),
            'empty_data' => static function () use ($fieldDescription) {
                return $fieldDescription->getAssociationAdmin()->getNewInstance();
            },
        ];

        if (isset($formOptions['by_reference'])) {
            $typeOptions['collection_by_reference'] = $formOptions['by_reference'];
        }

        return $typeOptions;
    }
}
