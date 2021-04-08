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

final class DatagridTest extends BasePantherTestCase
{
    public function testFilter(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/category/list');

        $this->client->clickLink('Filters');
        $this->client->clickLink('Name');

        $this->client->submitForm('Filter', [
            'filter[name][value]' => 'Dystopian',
        ]);

        self::assertSelectorTextContains('.sonata-link-identifier', 'Dystopian');
    }

    public function testFilterRespectingCaseSensitivity(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/author/list');

        $this->client->clickLink('Filters');
        $this->client->clickLink('Address Street');

        $this->client->submitForm('Filter', [
            'filter[address__street][value]' => 'Mancha',
        ]);

        self::assertSelectorTextContains('.sonata-link-identifier', 'Miguel de Cervantes');
    }

    public function testFilterNotRespectingCaseSensitivity(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/author/list');

        $this->client->clickLink('Filters');
        $this->client->clickLink('Address Street');

        $this->client->submitForm('Filter', [
            'filter[address__street][value]' => 'mancha',
        ]);

        self::assertSelectorTextNotContains('.sonata-link-identifier', 'Miguel de Cervantes');
    }
}
