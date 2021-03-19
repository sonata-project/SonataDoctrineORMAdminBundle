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

    public function testFindWithException(): void
    {
        $className = 'fakeClass';
        $id = 1;
        $revision = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('find')
            ->with($className, $id, $revision)
            ->willThrowException(new \Exception());

        $this->assertNull($this->auditReader->find($className, $id, $revision));
    }

    public function testFindRevisionHistory(): void
    {
        $limit = 20;
        $offset = 0;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('findRevisionHistory')
            ->with($limit, $offset)
            ->willReturn([]);

        $this->auditReader->findRevisionHistory('class', $limit, $offset);
    }

    public function testFindRevision(): void
    {
        $revision = 2;

        $this->simpleThingsAuditReader->expects($this->once())->method('findRevision')->with($revision);

        $this->auditReader->findRevision('class', $revision);
    }

    public function testFindRevisionWithException(): void
    {
        $revision = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('findRevision')
            ->with($revision)
            ->willThrowException(new \Exception());

        $this->assertNull($this->auditReader->findRevision('class', $revision));
    }

    public function testFindRevisions(): void
    {
        $className = 'fakeClass';
        $id = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('findRevisions')
            ->with($className, $id)
            ->willReturn([]);

        $this->auditReader->findRevisions($className, $id);
    }

    public function testFindRevisionsWithException(): void
    {
        $className = 'fakeClass';
        $id = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('findRevisions')
            ->with($className, $id)
            ->willThrowException(new \Exception());

        $this->assertSame([], $this->auditReader->findRevisions($className, $id));
    }

    public function testDiff(): void
    {
        $className = 'fakeClass';
        $id = 1;
        $oldRevision = 1;
        $newRevision = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('diff')
            ->with($className, $id, $oldRevision, $newRevision)
            ->willReturn([]);

        $this->auditReader->diff($className, $id, $oldRevision, $newRevision);
    }

    public function testDiffWithException(): void
    {
        $className = 'fakeClass';
        $id = 1;
        $oldRevision = 1;
        $newRevision = 2;

        $this->simpleThingsAuditReader
            ->expects($this->once())
            ->method('diff')
            ->with($className, $id, $oldRevision, $newRevision)
            ->willThrowException(new \Exception());

        $this->assertSame([], $this->auditReader->diff($className, $id, $oldRevision, $newRevision));
    }
}
