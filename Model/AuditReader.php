<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Model;

use Sonata\AdminBundle\Model\AuditReaderInterface;
use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;

class AuditReader implements AuditReaderInterface
{
    protected $auditReader;

    /**
     * @param \SimpleThings\EntityAudit\AuditReader $auditReader
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
}
