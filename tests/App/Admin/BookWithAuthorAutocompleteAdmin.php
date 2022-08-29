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

namespace Sonata\DoctrineORMAdminBundle\Tests\App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Book;

/**
 * @phpstan-extends AbstractAdmin<Book>
 */
final class BookWithAuthorAutocompleteAdmin extends AbstractAdmin
{
    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'book_with_author_autocomplete';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'book-with-author-autocomplete';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('author', ModelAutocompleteType::class, [
                'required' => false,
                'property' => ['name'],
                'minimum_input_length' => 0,
            ]);
    }
}
