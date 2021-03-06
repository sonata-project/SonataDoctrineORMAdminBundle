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
     * @param int $plainField
     */
    public function __construct(Embeddable\EmbeddedEntity $embeddedEntity, ?int $plainField = null)
    {
        $this->embeddedEntity = $embeddedEntity;
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
