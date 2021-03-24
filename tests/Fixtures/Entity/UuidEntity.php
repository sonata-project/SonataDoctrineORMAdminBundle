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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\NonIntegerIdentifierTestClass;

final class UuidEntity
{
    /**
     * @var NonIntegerIdentifierTestClass
     */
    private $uuid;

    public function __construct(NonIntegerIdentifierTestClass $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getId(): NonIntegerIdentifierTestClass
    {
        return $this->uuid;
    }
}
