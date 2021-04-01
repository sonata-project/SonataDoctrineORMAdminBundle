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

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
final class DoubleNameEntity
{
    /**
     * @Column(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @Column(type="string", nullable=true)
     *
     * @var string|null
     */
    public $name2;

    /**
     * @Id
     * @Column(type="integer")
     *
     * @var int
     */
    private $id;

    public function __construct(int $id, string $name, ?string $name2)
    {
        $this->id = $id;
        $this->name = $name;
        $this->name2 = $name2;
    }
}
