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

use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;

final class UlidChildEntityAdminTest extends BaseAdminTestCase
{
    public function provideAdminBaseUrl(): string
    {
        return '/admin/tests/app/ulidchildentity';
    }

    public static function provideCrudUrlsCases(): iterable
    {
        yield 'List Ulid Child Entity' => ['list'];
        yield 'Create Ulid Child Entity' => ['create'];
        yield 'Edit Ulid Child Entity' => ['01GY4D6JYD2KCVDCJS0JF17X90/edit'];
        yield 'Show Ulid Child Entity' => ['01GY4D6JYD2KCVDCJS0JF17X90/show'];
        yield 'Remove Ulid Child Entity' => ['01GY4D6JYD2KCVDCJS0JF17X90/delete'];
    }

    public static function provideFormsUrlsCases(): iterable
    {
        yield 'Create Ulid Child Entity' => ['create', [
            'uniqid' => 'ulidchildentity',
        ], 'btn_create_and_list', [
            'ulidchildentity[name]' => 'Name',
        ]];
    }

    public static function provideBatchActionsCases(): iterable
    {
        yield 'Delete all items' => ['delete', [], 0];
        yield 'Delete one item' => ['delete', ['01GY4D6JYD2KCVDCJS0JF17X90'], 4];
        yield 'Delete two items' => [
            'delete',
            ['01GY4D6JYD2KCVDCJS0JF17X90', '01GY4D6JYD2KCVDCJS0JF17X91'],
            3,
        ];
    }

    public static function provideFilterActionCases(): iterable
    {
        yield 'Filter by id' => [['id' => ['value' => '01GY4D6JYD2KCVDCJS0JF17X90']], 1];
        yield 'Filter by not id' => [['id' => [
            'value' => '01GY4D6JYD2KCVDCJS0JF17X90',
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
        ]], 4];

        yield 'Filter by name' => [['name' => ['value' => 'foo']], 1];

        yield 'Filter by parent' => [['parent' => [
            'value' => ['018788d3-4bcd-79d7-8acf-b14b787c7e04'],
        ]], 1];
        yield 'Filter by not parent' => [['parent' => [
            'value' => [
                '018788d3-4bcd-79d7-8acf-b14b787c7e04',
                '018788d3-4bcd-79d7-8acf-b14b7976583e',
                '018788d3-4bcd-79d7-8acf-b14b7a3d8247',
            ],
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
        ]], 2];
    }

    protected function prepareData(): void
    {
    }
}
