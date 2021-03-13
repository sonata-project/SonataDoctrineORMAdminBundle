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
use Sonata\AdminBundle\Builder\ShowBuilderInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface as DeprecatedTypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Guesser\TypeGuesser;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class ShowBuilder implements ShowBuilderInterface
{
    /**
     * NEXT_MAJOR: Restrict guesser type to TypeGuesserInterface.
     *
     * @var DeprecatedTypeGuesserInterface|TypeGuesserInterface
     */
    protected $guesser;

    /**
     * @var string[]
     */
    protected $templates;

    /**
     * NEXT_MAJOR: Restrict guesser type to TypeGuesserInterface.
     *
     * @param DeprecatedTypeGuesserInterface|TypeGuesserInterface $guesser
     * @param string[]                                            $templates
     */
    public function __construct($guesser, array $templates)
    {
        $this->guesser = $guesser;
        $this->templates = $templates;
    }

    public function getBaseList(array $options = [])
    {
        return new FieldDescriptionCollection();
    }

    public function addField(FieldDescriptionCollection $list, $type, FieldDescriptionInterface $fieldDescription, AdminInterface $admin)
    {
        if (null === $type) {
            // NEXT_MAJOR: Remove the condition and keep the if part.
            if ($this->guesser instanceof TypeGuesserInterface) {
                $guessType = $this->guesser->guess($fieldDescription);
            } else {
                $guessType = $this->guesser->guessType(
                    $admin->getClass(),
                    $fieldDescription->getName(),
                    $admin->getModelManager()
                );
            }
            $fieldDescription->setType($guessType->getType());
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($admin, $fieldDescription);
        $admin->addShowFieldDescription($fieldDescription->getName(), $fieldDescription);

        $list->add($fieldDescription);
    }

    public function fixFieldDescription(AdminInterface $admin, FieldDescriptionInterface $fieldDescription)
    {
        // NEXT_MAJOR: Remove this line.
        $fieldDescription->setAdmin($admin);

        // NEXT_MAJOR: Remove this block.
        if ($admin->getModelManager()->hasMetadata($admin->getClass(), 'sonata_deprecation_mute')) {
            [$metadata, $lastPropertyName, $parentAssociationMappings] = $admin->getModelManager()->getParentMetadataForProperty($admin->getClass(), $fieldDescription->getName(), 'sonata_deprecation_mute');
            $fieldDescription->setParentAssociationMappings($parentAssociationMappings);

            // set the default field mapping
            if (isset($metadata->fieldMappings[$lastPropertyName])) {
                $fieldDescription->setFieldMapping($metadata->fieldMappings[$lastPropertyName]);
            }

            // set the default association mapping
            if (isset($metadata->associationMappings[$lastPropertyName])) {
                $fieldDescription->setAssociationMapping($metadata->associationMappings[$lastPropertyName]);
            }
        }

        if (!$fieldDescription->getType()) {
            throw new \RuntimeException(sprintf('Please define a type for field `%s` in `%s`', $fieldDescription->getName(), \get_class($admin)));
        }

        if (!$fieldDescription->getTemplate()) {
            $fieldDescription->setTemplate($this->getTemplate($fieldDescription->getType()));
        }

        switch ($fieldDescription->getMappingType()) {
            case ClassMetadata::MANY_TO_ONE:
            case ClassMetadata::ONE_TO_ONE:
            case ClassMetadata::ONE_TO_MANY:
            case ClassMetadata::MANY_TO_MANY:
                $admin->attachAdminClass($fieldDescription);

                break;
        }
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
                'Overriding %s show template is deprecated since sonata-project/doctrine-orm-admin-bundle 3.19.'
                .' You should override %s show template instead.',
                $type,
                TypeGuesser::DEPRECATED_TYPES[$type]
            ), \E_USER_DEPRECATED);
        }

        return $this->templates[$type];
    }
}
