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

final class BatchActionsTest extends BaseFunctionalTestCase
{
    /**
     * @dataProvider provideBatchActionsCases
     */
    public function testBatchActions(string $url, string $action): void
    {
        $this->client->request(Request::METHOD_GET, $url);
        $this->client->submitForm('OK', [
            'all_elements' => true,
            'action' => $action,
        ]);
        $this->client->submitForm('Yes, execute');

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<array<string>>
     *
     * @phpstan-return iterable<array{0: string}>
     */
    public static function provideBatchActionsCases(): iterable
    {
        yield 'Normal delete' => ['/admin/tests/app/book/list', 'delete'];
        yield 'Joined delete' => ['/admin/tests/app/author/list', 'delete'];
        yield 'More than 20 items delete' => ['/admin/tests/app/sub/list', 'delete'];
    }
}
