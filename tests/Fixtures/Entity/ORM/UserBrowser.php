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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class UserBrowser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer")
     */
    private ?int $userId = null;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer")
     */
    private ?int $browserId = null;

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setBrowserId(int $browserId): void
    {
        $this->browserId = $browserId;
    }

    public function getBrowserId(): ?int
    {
        return $this->browserId;
    }
}
