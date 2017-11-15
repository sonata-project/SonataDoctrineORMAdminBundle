<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Model;

use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use Sonata\AdminBundle\Model\AuditReaderInterface;

class AuditReader implements AuditReaderInterface
{
    /**
     * @var SimpleThingsAuditReader
     */
    protected $auditReader;

    /**
     * @param SimpleThingsAuditReader $auditReader
     */
    public function __construct(SimpleThingsAuditReader $auditReader)
    {
        $this->auditReader = $auditReader;
    }

    /**
     * {@inheritdoc}
     */
    public function find($className, $id, $revision)
    {
        return $this->auditReader->find($className, $id, $revision);
    }

    /**
     * {@inheritdoc}
     */
    public function findRevisionHistory($className, $limit = 20, $offset = 0)
    {
        return $this->auditReader->findRevisionHistory($limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findRevision($classname, $revision)
    {
        return $this->auditReader->findRevision($revision);
    }

    /**
     * {@inheritdoc}
     */
    public function findRevisions($className, $id)
    {
        return $this->auditReader->findRevisions($className, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function diff($className, $id, $oldRevision, $newRevision)
    {
        return $this->auditReader->diff($className, $id, $oldRevision, $newRevision);
    }
}
