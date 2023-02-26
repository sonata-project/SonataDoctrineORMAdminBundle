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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
#[ORM\Entity]
final class DoubleNameEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    public $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    #[ORM\Column(type: Types::STRING)]
    public $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public $name2;

    public function __construct(int $id, string $name, ?string $name2)
    {
        $this->id = $id;
        $this->name = $name;
        $this->name2 = $name2;
    }
}
