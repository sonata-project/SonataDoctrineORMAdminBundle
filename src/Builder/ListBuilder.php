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
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\ListBuilderInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ListBuilder implements ListBuilderInterface
{
    /**
     * @var TypeGuesserInterface
     */
    protected $guesser;

    /**
     * @var string[]
     */
    protected $templates = [];

    /**
     * @param string[] $templates
     */
    public function __construct(TypeGuesserInterface $guesser, array $templates = [])
    {
        $this->guesser = $guesser;
        $this->templates = $templates;
    }

    public function getBaseList(array $options = [])
    {
        return new FieldDescriptionCollection();
    }

    public function buildField($type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
    {
        if (null === $type) {
            $guessType = $this->guesser->guessType(
                $admin->getClass(),
                $fieldDescription->getName(),
                $admin->getModelManager()
            );
            $fieldDescription->setType($guessType->getType() ?: '_action');
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
    }

    public function addField(FieldDescriptionCollection $list, $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
    {
        $this->buildField($type, $fieldDescription, $admin);
        $admin->addListFieldDescription($fieldDescription->getName(), $fieldDescription);

        $list->add($fieldDescription);
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        if ('_action' === $fieldDescription->getName() || 'actions' === $fieldDescription->getType()) {
            $this->buildActionFieldDescription($fieldDescription);
        }

        $fieldDescription->setAdmin($admin);

        // NEXT_MAJOR: Remove this block.
        if ($admin->getModelManager()->hasMetadata($admin->getClass(), 'sonata_deprecation_mute')) {
            [$metadata, $lastPropertyName, $parentAssociationMappings] = $admin->getModelManager()->getParentMetadataForProperty($admin->getClass(), $fieldDescription->getName());
            $fieldDescription->setParentAssociationMappings($parentAssociationMappings);

            // set the default field mapping
            if (isset($metadata->fieldMappings[$lastPropertyName])) {
                $fieldDescription->setFieldMapping($metadata->fieldMappings[$lastPropertyName]);
                if (false !== $fieldDescription->getOption('sortable')) {
                    $fieldDescription->setOption('sortable', $fieldDescription->getOption('sortable', true));
                    $fieldDescription->setOption('sort_parent_association_mappings', $fieldDescription->getOption('sort_parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
                    $fieldDescription->setOption('sort_field_mapping', $fieldDescription->getOption('sort_field_mapping', $fieldDescription->getFieldMapping()));
                }
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$lastPropertyName])) {
                $fieldDescription->setAssociationMapping($metadata->associationMappings[$lastPropertyName]);
            }

            $fieldDescription->setOption('_sort_order', $fieldDescription->getOption('_sort_order', 'ASC'));
        }

        // NEXT_MAJOR: Uncomment this code.
        //if ([] !== $fieldDescription->getFieldMapping()) {
        //    if (false !== $fieldDescription->getOption('sortable')) {
        //        $fieldDescription->setOption('sortable', $fieldDescription->getOption('sortable', true));
        //        $fieldDescription->setOption('sort_parent_association_mappings', $fieldDescription->getOption('sort_parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
        //        $fieldDescription->setOption('sort_field_mapping', $fieldDescription->getOption('sort_field_mapping', $fieldDescription->getFieldMapping()));
        //    }
        //
        //    $fieldDescription->setOption('_sort_order', $fieldDescription->getOption('_sort_order', 'ASC'));
        //}

        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                \get_class($admin)
            ));
        }

        $fieldDescription->setOption('code', $fieldDescription->getOption('code', $fieldDescription->getName()));
        $fieldDescription->setOption('label', $fieldDescription->getOption('label', $fieldDescription->getName()));

        if (!$fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate($this->getTemplate($fieldDescription->getType()));

            if (!$fieldDescription->getTemplate()) {
                switch ($fieldDescription->getMappingType()) {
                    case ClassMetadata::MANY_TO_ONE:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_many_to_one.html.twig'
                        );

                        break;
                    case ClassMetadata::ONE_TO_ONE:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_one_to_one.html.twig'
                        );

                        break;
                    case ClassMetadata::ONE_TO_MANY:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_one_to_many.html.twig'
                        );

                        break;
                    case ClassMetadata::MANY_TO_MANY:
                        $fieldDescription->setTemplate(
                            '@SonataAdmin/CRUD/Association/list_many_to_many.html.twig'
                        );

                        break;
                }
            }
        }

        if (\in_array($fieldDescription->getMappingType(), [
            ClassMetadata::MANY_TO_ONE,
            ClassMetadata::ONE_TO_ONE,
            ClassMetadata::ONE_TO_MANY,
            ClassMetadata::MANY_TO_MANY,
        ], true)) {
            $admin->attachAdminClass($fieldDescription);
        }
    }

    /**
     * @return FieldDescriptionInterface
     */
    public function buildActionFieldDescription(FieldDescriptionInterface $fieldDescription)
    {
        if (null === $fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate('@SonataAdmin/CRUD/list__action.html.twig');
        }

        if (\in_array($fieldDescription->getType(), [null, '_action'], true)) {
            $fieldDescription->setType('actions');
        }

        if (null === $fieldDescription->getOption('name')) {
            $fieldDescription->setOption('name', 'Action');
        }

        if (null === $fieldDescription->getOption('code')) {
            $fieldDescription->setOption('code', 'Action');
        }

        if (null !== $fieldDescription->getOption('actions')) {
            $actions = $fieldDescription->getOption('actions');
            foreach ($actions as $k => $action) {
                if (!isset($action['template'])) {
                    $actions[$k]['template'] = sprintf('@SonataAdmin/CRUD/list__action_%s.html.twig', $k);
                }
            }

            $fieldDescription->setOption('actions', $actions);
        }

        return $fieldDescription;
    }

    private function getTemplate(string $type): ?string
    {
        if (!isset($this->templates[$type])) {
            // NEXT_MAJOR: Remove the check for deprecated type and always return null.
            if (isset(TypeGuesser::DEPRECATED_TYPES[$type])) {
                return $this->getTemplate(TypeGuesser::DEPRECATED_TYPES[$type]);
            }

            return null;
        }

        // NEXT_MAJOR: Remove the deprecation.
        if (isset(TypeGuesser::DEPRECATED_TYPES[$type])) {
            @trigger_error(sprintf(
                'Overriding %s list template is deprecated since sonata-project/doctrine-orm-admin-bundle 3.19.'
                .' You should override %s list template instead.',
                $type,
                TypeGuesser::DEPRECATED_TYPES[$type]
            ), \E_USER_DEPRECATED);
        }

        return $this->templates[$type];
    }
}
