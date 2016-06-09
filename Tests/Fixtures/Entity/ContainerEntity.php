<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class ContainerEntity
{
    /**
     * @var int
     */
    protected $plainField;

    /**
     * @var AssociatedEntity
     */
    protected $associatedEntity;

    /**
     * @var Embeddable\EmbeddedEntity
     */
    protected $embeddedEntity;

    /**
     * @param AssociatedEntity          $associatedEntity
     * @param Embeddable\EmbeddedEntity $embeddedEntity
     */
    public function __construct(AssociatedEntity $associatedEntity, Embeddable\EmbeddedEntity $embeddedEntity)
    {
        $this->associatedEntity = $associatedEntity;
        $this->embeddedEntity = $embeddedEntity;
    }

    /**
     * @return AssociatedEntity
     */
    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }
}
