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

final class UuidEntityAdminTest extends BaseAdminTestCase
{
    public function provideAdminBaseUrl(): string
    {
        return '/admin/tests/app/uuidentity';
    }

    public static function provideCrudUrlsCases(): iterable
    {
        yield 'List Uuid Entity' => ['list'];
        yield 'Create Uuid Entity' => ['create'];
        yield 'Edit Uuid Entity' => ['018788d3-4bcd-79d7-8acf-b14b787c7e04/edit'];
        yield 'Show Uuid Entity' => ['018788d3-4bcd-79d7-8acf-b14b787c7e04/show'];
        yield 'Remove Uuid Entity' => ['018788d3-4bcd-79d7-8acf-b14b787c7e04/delete'];
    }

    public static function provideFormsUrlsCases(): iterable
    {
        yield 'Create Uuid Entity' => ['create', [
            'uniqid' => 'uuidentity',
        ], 'btn_create_and_list', [
            'uuidentity[name]' => 'Name',
        ]];
    }

    public static function provideBatchActionsCases(): iterable
    {
        yield 'Delete all items' => ['delete', [], 0];
        yield 'Delete one item' => ['delete', ['018788d3-4bcd-79d7-8acf-b14b787c7e04'], 5];
        yield 'Delete two items' => [
            'delete',
            ['018788d3-4bcd-79d7-8acf-b14b787c7e04', '018788d3-4bcd-79d7-8acf-b14b7976583e'],
            4,
        ];
    }

    public static function provideFilterActionCases(): iterable
    {
        yield 'Filter by id' => [['id' => ['value' => '018788d3-4bcd-79d7-8acf-b14b787c7e04']], 1];
        yield 'Filter by not id' => [['id' => [
            'value' => '018788d3-4bcd-79d7-8acf-b14b787c7e04',
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
        ]], 5];

        yield 'Filter by name' => [['name' => ['value' => '2000 foo']], 1];

        yield 'Filter by child' => [['child' => [
            'value' => [
                '01GY4D6JYD2KCVDCJS0JF17X90',
                '01GY4D6JYD2KCVDCJS0JF17X93',
            ],
        ]], 2];
        yield 'Filter by not child' => [['child' => [
            'value' => ['01GY4D6JYD2KCVDCJS0JF17X90'],
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
        ]], 5];

        yield 'Filter by car' => [['car' => [
            'value' => 'Foo~2000',
        ]], 2];
        yield 'Filter by not car' => [['car' => [
            'value' => 'Foo~2010',
            'type' => EqualOperatorType::TYPE_NOT_EQUAL,
        ]], 5];
    }

    protected function prepareData(): void
    {
    }
}
