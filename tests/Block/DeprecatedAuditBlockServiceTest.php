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

use Prophecy\Argument;
use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use SimpleThings\EntityAudit\Revision;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\Block;
use Sonata\BlockBundle\Test\BlockServiceTestCase;
use Sonata\DoctrineORMAdminBundle\Block\AuditBlockService;

/**
 * NEXT_MAJOR: Remove this class.
 *
 * @group legacy
 *
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class DeprecatedAuditBlockServiceTest extends BlockServiceTestCase
{
    private $simpleThingsAuditReader;
    private $blockService;

    protected function setUp(): void
    {
        if (!property_exists($this, 'templating')) {
            $this->markTestSkipped(sprintf(
                '%s requires sonata-project/block-bundle < 3.18.4.',
                __CLASS__
            ));
        }

        parent::setUp();
        $this->simpleThingsAuditReader = $this->prophesize(SimpleThingsAuditReader::class);

        $this->blockService = new AuditBlockService(
            'block.service',
            $this->templating,
            $this->simpleThingsAuditReader->reveal()
        );
    }

    /**
     * @expectedDeprecation Passing Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as argument 2 to Sonata\DoctrineORMAdminBundle\Block\AuditBlockService::__construct() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will throw a \TypeError in version 4.0. You must pass an instance of SimpleThings\EntityAudit\AuditReader instead.
     */
    public function testExecute(): void
    {
        $blockContext = $this->prophesize(BlockContextInterface::class);

        $blockContext->getBlock()->willReturn($block = new Block())->shouldBeCalledTimes(1);
        $blockContext->getSetting('limit')->willReturn($limit = 10)->shouldBeCalledTimes(1);

        $this->simpleThingsAuditReader->findRevisionHistory($limit, 0)
            ->willReturn([$revision = new Revision('test', '123', 'test')])
            ->shouldBeCalledTimes(1);

        $this->simpleThingsAuditReader->findEntitiesChangedAtRevision(Argument::cetera())
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $blockContext->getTemplate()->willReturn('template')->shouldBeCalledTimes(1);
        $blockContext->getSettings()->willReturn([])->shouldBeCalledTimes(1);

        $this->blockService->execute($blockContext->reveal());

        $this->assertSame('template', $this->templating->view);
        $this->assertIsArray($this->templating->parameters['settings']);
        $this->assertSame($revision, $this->templating->parameters['revisions'][0]['revision']);
        $this->assertSame($block, $this->templating->parameters['block']);
    }

    /**
     * @expectedDeprecation Passing Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as argument 2 to Sonata\DoctrineORMAdminBundle\Block\AuditBlockService::__construct() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will throw a \TypeError in version 4.0. You must pass an instance of SimpleThings\EntityAudit\AuditReader instead.
     */
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
