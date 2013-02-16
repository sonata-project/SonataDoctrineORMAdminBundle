<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Block;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

use SimpleThings\EntityAudit\AuditReader;

/**
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AuditBlockService extends BaseBlockService
{
    protected $auditReader;

    /**
     * @param string                                                     $name
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param \SimpleThings\EntityAudit\AuditReader                      $auditReader
     */
    public function __construct($name, EngineInterface $templating, AuditReader $auditReader)
    {
        parent::__construct($name, $templating);

        $this->auditReader = $auditReader;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockInterface $block, Response $response = null)
    {
        $settings = array_merge($this->getDefaultSettings(), $block->getSettings());

        $revisions = array();

        foreach ($this->auditReader->findRevisionHistory($settings['limit'], 0) as $revision) {
            $revisions[] = array(
                'revision' => $revision,
                'entities' => $this->auditReader->findEntitesChangedAtRevision($revision->getRev())
            );
        }

        return $this->renderResponse('SonataDoctrineORMAdminBundle:Block:block_audit.html.twig', array(
            'block'     => $block,
            'settings'  => $settings,
            'revisions' => $revisions,
        ), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
        // TODO: Implement validateBlock() method.
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Audit List';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings()
    {
        return array(
            'limit' => 10
        );
    }
}
