<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class AssociatedEntity
{
    /**
     * @var int
     */
    protected $plainField;

    /**
     * @var Embeddable\EmbeddedEntity
     */
    protected $embeddedEntity;

    /**
     * AssociatedEntity constructor.
     *
     * @param int                       $plainField
     * @param Embeddable\EmbeddedEntity $embeddedEntity
     */
    public function __construct($plainField = null, Embeddable\EmbeddedEntity $embeddedEntity)
    {
        $this->plainField = $plainField;
        $this->embeddedEntity = $embeddedEntity;
    }

    /**
     * @return int
     */
    public function getPlainField()
    {
        return $this->plainField;
    }
}
