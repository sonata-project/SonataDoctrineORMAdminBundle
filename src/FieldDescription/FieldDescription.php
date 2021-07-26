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

namespace Sonata\DoctrineORMAdminBundle\FieldDescription;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\FieldDescription\BaseFieldDescription;

final class FieldDescription extends BaseFieldDescription
{
    public function getTargetModel(): ?string
    {
        return $this->associationMapping['targetEntity'] ?? null;
    }

    public function isIdentifier(): bool
    {
        return $this->fieldMapping['id'] ?? false;
    }

    public function getValue(object $object)
    {
        foreach ($this->parentAssociationMappings as $parentAssociationMapping) {
            $object = $this->getFieldValue($object, $parentAssociationMapping['fieldName']);
        }

        return $this->getFieldValue($object, $this->getFieldName());
    }

    public function describesSingleValuedAssociation(): bool
    {
        return \is_int($this->mappingType) && $this->mappingType === ($this->mappingType & ClassMetadata::TO_ONE);
    }

    public function describesCollectionValuedAssociation(): bool
    {
        return \is_int($this->mappingType) && $this->mappingType === ($this->mappingType & ClassMetadata::TO_MANY);
    }

    protected function setFieldMapping(array $fieldMapping): void
    {
        $this->fieldMapping = $fieldMapping;

        $this->type = $this->type ?? (string) $fieldMapping['type'];
        $this->mappingType = $this->mappingType ?? $fieldMapping['type'];
    }

    protected function setAssociationMapping(array $associationMapping): void
    {
        $this->associationMapping = $associationMapping;

        $this->type = $this->type ?? (string) $associationMapping['type'];
        $this->mappingType = $this->mappingType ?? $associationMapping['type'];
    }

    protected function setParentAssociationMappings(array $parentAssociationMappings): void
    {
        foreach ($parentAssociationMappings as $parentAssociationMapping) {
            if (!\is_array($parentAssociationMapping)) {
                throw new \InvalidArgumentException('An association mapping must be an array');
            }
        }

        $this->parentAssociationMappings = $parentAssociationMappings;
    }
}
