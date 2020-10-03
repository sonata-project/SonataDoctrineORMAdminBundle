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

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use Sonata\DoctrineORMAdminBundle\Model\AuditReader;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class AuditReaderTest extends TestCase
{
    private $simpleThingsAuditReader;
    private $auditReader;

    protected function setUp(): void
    {
        $this->simpleThingsAuditReader = $this->createMock(SimpleThingsAuditReader::class);
        $this->auditReader = new AuditReader($this->simpleThingsAuditReader);
    }

    public function testFind(): void
    {
        $className = 'fakeClass';
        $id = 1;
        $revision = 2;

        $this->simpleThingsAuditReader->expects($this->once())->method('find')->with($className, $id, $revision);

        $this->auditReader->find($className, $id, $revision);
    }

    public function testFindRevisionHistory(): void
    {
        $limit = 20;
        $offset = 0;

        $this->simpleThingsAuditReader->expects($this->once())->method('findRevisionHistory')->with($limit, $offset);

        $this->auditReader->findRevisionHistory(null, $limit, $offset);
    }

    public function testFindRevision(): void
    {
        $revision = 2;

        $this->simpleThingsAuditReader->expects($this->once())->method('findRevision')->with($revision);

        $this->auditReader->findRevision(null, $revision);
    }

    public function testFindRevisions(): void
    {
        $className = 'fakeClass';
        $id = 2;

        $this->simpleThingsAuditReader->expects($this->once())->method('findRevisions')->with($className, $id);

        $this->auditReader->findRevisions($className, $id);
    }

    public function testDiff(): void
    {
        $className = 'fakeClass';
        $id = 1;
        $oldRevision = 1;
        $newRevision = 2;

        $this->simpleThingsAuditReader->expects($this->once())->method('diff')
            ->with($className, $id, $oldRevision, $newRevision);

        $this->auditReader->diff($className, $id, $oldRevision, $newRevision);
    }
}
