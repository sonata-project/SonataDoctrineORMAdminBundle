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

namespace Sonata\DoctrineORMAdminBundle\Tests\Functional\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseAdminTestCase extends WebTestCase
{
    /**
     * @dataProvider provideCrudUrlsCases
     *
     * @param array<string, mixed> $parameters
     */
    public function testCrudUrls(string $url, array $parameters = []): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $this->provideAdminBaseUrl().'/'.$url, $parameters);

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideFormsUrlsCases
     *
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $fieldValues
     */
    public function testFormsUrls(string $url, array $parameters, string $button, array $fieldValues = []): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $this->provideAdminBaseUrl().'/'.$url, $parameters);
        $client->submitForm($button, $fieldValues);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideBatchActionsCases
     *
     * @param array<string> $idx
     */
    public function testBatchActions(string $action, array $idx = [], ?int $rowsAfter = null): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $this->provideAdminBaseUrl().'/list');

        $client->submitForm('OK', [
            'action' => $action,
            (0 === \count($idx) ? 'all_elements' : 'idx') => 0 === \count($idx) ? true : $idx,
        ]);
        $client->submitForm('Yes, execute');
        $crawler = $client->followRedirect();

        if (null !== $rowsAfter) {
            static::assertCount($rowsAfter, $crawler->filter('.sonata-ba-list tbody tr'));
        }

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideFilterActionCases
     *
     * @param array<string, array{value: string|array<string>}> $filters
     */
    public function testFilterAction(array $filters, int $count): void
    {
        $client = self::createClient();

        $this->prepareData();

        $crawler = $client->request('GET', $this->provideAdminBaseUrl().'/list', [
            'filter' => $filters,
        ]);

        self::assertResponseIsSuccessful();
        static::assertCount($count, $crawler->filter('.sonata-ba-list tbody tr'));
    }

    abstract public function provideAdminBaseUrl(): string;

    /**
     * @return iterable<array<string|array<string, mixed>>>
     *
     * @phpstan-return iterable<array{0: string, 1?: array<string, mixed>}>
     */
    abstract public static function provideCrudUrlsCases(): iterable;

    /**
     * @return iterable<array<string|array<string, mixed>>>
     *
     * @phpstan-return iterable<array{0: string, 1: array<string, mixed>, 2: string, 3?: array<string, mixed>}>
     */
    abstract public static function provideFormsUrlsCases(): iterable;

    /**
     * @return iterable<array<string|array|int>>
     *
     * @phpstan-return iterable<array{0: string, 1?: array<string>, 2?: integer}>
     */
    abstract public static function provideBatchActionsCases(): iterable;

    /**
     * @return iterable<array<int|array>>
     *
     * @phpstan-return iterable<array{0: array<string, array{value: string|array<string>, type?: integer}>, 1: integer}>
     */
    abstract public static function provideFilterActionCases(): iterable;

    abstract protected function prepareData(): void;
}
