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

final class UuidEntity
{
    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getId()
    {
        return $this->uuid;
    }
}
