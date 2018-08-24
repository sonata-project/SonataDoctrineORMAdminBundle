<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Menu
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @ORM\ManyToOne(targetEntity="Menu")
     * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
     */
    private $root;

    /**
     * @ORM\ManyToOne(targetEntity="Menu", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Menu", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;
}
