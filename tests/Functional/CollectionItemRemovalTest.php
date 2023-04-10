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

namespace Sonata\DoctrineORMAdminBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Panther\DomCrawler\Crawler;

final class CollectionItemRemovalTest extends BasePantherTestCase
{
    public function testRemoveCollectionItemWithoutValidation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/tests/app/mother/1/edit?uniqid=mother');

        $form = $crawler->selectButton('Update')->form();
        $form['mother[children][0][name]'] = '';

        $crawler->filter('.icheckbox_square-blue')->each(static function (Crawler $label): void {
            $label->click();
        });

        $this->client->submit($form);

        self::assertSelectorTextContains('.alert-success', 'Item "1" has been successfully updated.');
    }

    public function testTriggerCollectionValidation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/tests/app/mother/1/edit?uniqid=mother');

        $form = $crawler->selectButton('Update')->form();
        $form['mother[children][0][name]'] = '';

        $this->client->submit($form);

        self::assertSelectorTextContains('.alert-error', 'Item "1" has been successfully updated.');
    }
}
