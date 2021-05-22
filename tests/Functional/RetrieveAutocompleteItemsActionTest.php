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

final class RetrieveAutocompleteItemsActionTest extends BaseFunctionalTestCase
{
    public function testAutocomplete(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/core/get-autocomplete-items?q=autocompletion&_per_page=10&_page=1&uniqid=s608eac968661e&_sonata_admin=Sonata%5CDoctrineORMAdminBundle%5CTests%5CApp%5CAdmin%5CBookWithAuthorAutocompleteAdmin&field=author');

        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        $response = json_decode($content, true);
        self::assertIsArray($response);

        self::assertIsArray($response['items']);
        self::assertCount(1, $response['items']);

        $author = reset($response['items']);
        self::assertIsArray($author);

        self::assertArrayHasKey('id', $author);
        self::assertSame('autocompletion_author', $author['id']);

        self::assertResponseIsSuccessful();
    }
}
