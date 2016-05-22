<?php

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util;

/**
 * This class is used in the ModelManagerTest suite to test non integer/string identifiers
 *
 * @author Jeroen Thora <jeroen.thora@gmail.com>
 */
class NonIntegerIdentifierTestClass
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @param string $uuid
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->uuid;
    }
}
