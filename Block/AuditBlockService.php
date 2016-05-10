<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Block;

use SimpleThings\EntityAudit\AuditReader;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AuditBlockService extends BaseBlockService
{
    /**
     * @var AuditReader
     */
    protected $auditReader;

    /**
     * @param string          $name
     * @param EngineInterface $templating
     * @param AuditReader     $auditReader
     */
    public function __construct($name, EngineInterface $templating, AuditReader $auditReader)
    {
        parent::__construct($name, $templating);

        $this->auditReader = $auditReader;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $revisions = array();

        foreach ($this->auditReader->findRevisionHistory($blockContext->getSetting('limit'), 0) as $revision) {
            $revisions[] = array(
                'revision' => $revision,
                'entities' => $this->auditReader->findEntitesChangedAtRevision($revision->getRev()),
            );
        }

        return $this->renderResponse($blockContext->getTemplate(), array(
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'revisions' => $revisions,
        ), $response);
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
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'limit' => 10,
            'template' => 'SonataDoctrineORMAdminBundle:Block:block_audit.html.twig',
        ));
    }
}
