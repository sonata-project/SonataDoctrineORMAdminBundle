<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use Sonata\DoctrineORMAdminBundle\Model\ModelManager;

class ModelManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterEmpty()
    {
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $manager = new ModelManager($registry);
    }

}
