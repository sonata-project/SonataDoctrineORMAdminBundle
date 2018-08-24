<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class SimpleEntity
{
    private $schmeckles;
    private $multiWordProperty;

    public function getSchmeckles()
    {
        return $this->schmeckles;
    }

    public function setSchmeckles($value)
    {
        $this->schmeckles = $value;
    }

    public function getMultiWordProperty()
    {
        return $this->multiWordProperty;
    }

    public function setMultiWordProperty($value)
    {
        $this->multiWordProperty = $value;
    }
}
