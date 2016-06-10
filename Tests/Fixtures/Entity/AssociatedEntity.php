<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class AssociatedEntity
{
    /**
     * @var int
     */
    protected $plainField;

    /**
     * AssociatedEntity constructor.
     *
     * @param int $plainField
     */
    public function __construct($plainField = null)
    {
        $this->plainField = $plainField;
    }

    /**
     * @return int
     */
    public function getPlainField()
    {
        return $this->plainField;
    }
}
