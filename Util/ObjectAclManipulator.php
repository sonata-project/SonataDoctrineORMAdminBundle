<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Util;

use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Console\Output\OutputInterface;

use Sonata\AdminBundle\Security\Handler\AclSecurityHandlerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Util\ObjectAclManipulator as BaseObjectAclManipulator;

class ObjectAclManipulator extends BaseObjectAclManipulator
{
    /**
     * {@inheritDoc}
     */
    public function batchConfigureAcls(OutputInterface $output, AdminInterface $admin, UserSecurityIdentity $securityIdentity = null)
    {
        $securityHandler = $admin->getSecurityHandler();
        if (!$securityHandler instanceof AclSecurityHandlerInterface) {
            $output->writeln('Admin class is not configured to use ACL : <info>ignoring</info>');
            return;
        }

        $em = $admin->getModelManager()->getEntityManager();
        $datagrid = $admin->getDatagrid();
        $datagrid->buildPager();
        $query = $datagrid->getQuery();

        $query->setFirstResult(null);
        $query->setMaxResults(null);

        $count = 0;
        $countUpdated = 0;
        $countAdded = 0;

        try {
            $batchSize = 20;
            $batchSizeOutput = 200;
            $i = 0;
            $oids = array();
            foreach ($query->getQuery()->iterate() as $row) {
                $oids[] = ObjectIdentity::fromDomainObject($row[0]);

                // detach from Doctrine, so that it can be Garbage-Collected immediately
                $em->detach($row[0]);

                if ((++$i % $batchSize) == 0) {

                    list($batchAdded, $batchUpdated) = $this->configureAcls($admin, $oids, $securityIdentity);
                    $countAdded += $batchAdded;
                    $countUpdated += $batchUpdated;
                    $oids = array();
                }

                if ((++$i % $batchSizeOutput) == 0) {
                    $output->writeln(sprintf('   - generated class ACEs for %s objects (added %s, updated %s)', $count, $countAdded, $countUpdated));
                }

                $count++;
            }

            if (count($oids) > 0) {
                list($batchAdded, $batchUpdated) = $this->configureAcls($admin, $oids, $securityIdentity);
                $countAdded += $batchAdded;
                $countUpdated += $batchUpdated;
            }
        } catch ( \PDOException $e ) {
            throw new ModelManagerException('', 0, $e);
        }

        $output->writeln(sprintf('   - [TOTAL] generated class ACEs for %s objects (added %s, updated %s)', $count, $countAdded, $countUpdated));
    }
}