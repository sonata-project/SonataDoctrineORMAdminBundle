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

namespace Sonata\DoctrineORMAdminBundle\Tests\Block;

use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use Sonata\BlockBundle\Test\BlockServiceTestCase;
use Sonata\DoctrineORMAdminBundle\Block\AuditBlockService;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class AuditBlockServiceTest extends BlockServiceTestCase
{
    private $simpleThingsAuditReader;
    private $blockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleThingsAuditReader = $this->prophesize(SimpleThingsAuditReader::class);

        $this->blockService = new AuditBlockService(
            $this->twig,
            $this->simpleThingsAuditReader->reveal()
        );
    }

    public function testDefaultSettings(): void
    {
        $blockContext = $this->getBlockContext($this->blockService);

        $this->assertSettings([
            'attr' => [],
            'extra_cache_keys' => [],
            'limit' => 10,
            'template' => '@SonataDoctrineORMAdmin/Block/block_audit.html.twig',
            'ttl' => 0,
            'use_cache' => true,
        ], $blockContext);
    }
}
