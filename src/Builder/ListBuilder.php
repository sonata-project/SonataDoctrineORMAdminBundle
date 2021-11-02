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
use Sonata\AdminBundle\Builder\ListBuilderInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionCollection;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;

/**
 * @psalm-suppress DeprecatedInterface
 *
 * @see https://github.com/sonata-project/SonataAdminBundle/pull/7519
 */
final class ListBuilder implements ListBuilderInterface
{
    /**
     * @var TypeGuesserInterface
     */
    private $guesser;

    /**
     * @var string[]
     */
    private $templates = [];

    /**
     * @param string[] $templates
     */
    public function __construct(TypeGuesserInterface $guesser, array $templates = [])
    {
        $this->guesser = $guesser;
        $this->templates = $templates;
    }

    public function getBaseList(array $options = []): FieldDescriptionCollection
    {
        return new FieldDescriptionCollection();
    }

    public function buildField(?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        if (null === $type) {
            $guessType = $this->guesser->guess($fieldDescription);
            if (null === $guessType) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot guess a type for the field description "%s", You MUST provide a type.',
                    $fieldDescription->getName()
                ));
            }

            $fieldDescription->setType($guessType->getType());
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($fieldDescription);
    }

    public function addField(FieldDescriptionCollection $list, ?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        $this->buildField($type, $fieldDescription);
        $fieldDescription->getAdmin()->addListFieldDescription($fieldDescription->getName(), $fieldDescription);

        $list->add($fieldDescription);
    }

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        $type = $fieldDescription->getType();
        if (null === $type) {
            throw new \RuntimeException(sprintf(
                'Please define a type for field `%s` in `%s`',
                $fieldDescription->getName(),
                \get_class($fieldDescription->getAdmin())
            ));
        }

        if (ListMapper::TYPE_ACTIONS === $type) {
            $this->buildActionFieldDescription($fieldDescription);
        }

        if ([] !== $fieldDescription->getFieldMapping()) {
            if (false !== $fieldDescription->getOption('sortable')) {
                $fieldDescription->setOption('sortable', $fieldDescription->getOption('sortable', true));
                $fieldDescription->setOption('sort_parent_association_mappings', $fieldDescription->getOption('sort_parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
                $fieldDescription->setOption('sort_field_mapping', $fieldDescription->getOption('sort_field_mapping', $fieldDescription->getFieldMapping()));
            }

            $fieldDescription->setOption('_sort_order', $fieldDescription->getOption('_sort_order', 'ASC'));
        }

        if (null === $fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate($this->getTemplate($type));

            if (null === $fieldDescription->getTemplate()) {
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

        if ($fieldDescription->describesAssociation()) {
            $fieldDescription->getAdmin()->attachAdminClass($fieldDescription);
        }
    }

    private function buildActionFieldDescription(FieldDescriptionInterface $fieldDescription): FieldDescriptionInterface
    {
        if (null === $fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate('@SonataAdmin/CRUD/list__action.html.twig');
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
            return null;
        }

        return $this->templates[$type];
    }
}
