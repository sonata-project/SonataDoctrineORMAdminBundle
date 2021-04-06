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

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineExtensions\Query\Mysql\Binary;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class StringFilter extends Filter
{
    public const TRIM_NONE = 0;
    public const TRIM_LEFT = 1;
    public const TRIM_RIGHT = 2;
    public const TRIM_BOTH = self::TRIM_LEFT | self::TRIM_RIGHT;

    public const CHOICES = [
        StringOperatorType::TYPE_CONTAINS => 'LIKE',
        StringOperatorType::TYPE_STARTS_WITH => 'LIKE',
        StringOperatorType::TYPE_ENDS_WITH => 'LIKE',
        StringOperatorType::TYPE_NOT_CONTAINS => 'NOT LIKE',
        StringOperatorType::TYPE_EQUAL => '=',
        StringOperatorType::TYPE_NOT_EQUAL => '<>',
    ];

    /**
     * Filtering types do not make sense for searching by empty value.
     */
    private const MEANINGLESS_TYPES = [
        StringOperatorType::TYPE_CONTAINS,
        StringOperatorType::TYPE_STARTS_WITH,
        StringOperatorType::TYPE_ENDS_WITH,
        StringOperatorType::TYPE_NOT_CONTAINS,
    ];

    /**
     * @var Configuration|null
     */
    private $doctrineConfig;

    /**
     * NEXT_MAJOR: Do not accept null value in argument 1.
     */
    public function __construct(?EntityManagerInterface $em = null)
    {
        if (null === $em) {
            @trigger_error(sprintf(
                'Omitting or passing other type than "%s" as argument 1 to "%s()" is deprecated since'
                .' sonata-project/doctrine-orm-admin-bundle 3.x and will be not allowed in version 4.0.',
                EntityManagerInterface::class,
                __METHOD__
            ), \E_USER_DEPRECATED);
        }
        $this->doctrineConfig = $em ? $em->getConfiguration() : null;
        // NEXT_MAJOR: Replace the previous line with the following one.
        // $this->doctrineConfig = $em->getConfiguration();
    }

    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to "%s()" is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ), \E_USER_DEPRECATED);
        }

        if (!\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        $data['value'] = $this->trim((string) ($data['value'] ?? ''));
        $type = $data['type'] ?? StringOperatorType::TYPE_CONTAINS;

        // ignore empty value if it doesn't make sense
        if ('' === $data['value'] &&
            (!$this->getOption('allow_empty') || \in_array($type, self::MEANINGLESS_TYPES, true))
        ) {
            return;
        }

        // NEXT_MAJOR: Remove this if and the (int) cast.
        if (!\is_int($type)) {
            @trigger_error(sprintf(
                'Passing a non integer type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw a %s error in version 4.0.',
                \TypeError::class
            ), \E_USER_DEPRECATED);
        }
        $operator = $this->getOperator((int) $type);

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($query);

        $caseSensitive = $this->getOption('case_sensitive');

        if (false === $caseSensitive && '' !== $data['value']) {
            $clause = 'LOWER(%s.%s) %s :%s';
        } elseif (true === $caseSensitive && '' !== $data['value'] && null !== $this->doctrineConfig
            && Binary::class === $this->doctrineConfig->getCustomStringFunction('binary')
        ) {
            // NEXT_MAJOR: Remove the type check against `$this->doctrineConfig` in the `elseif` condition.
            // @see https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html#operator_like.
            $clause = '%s.%s %s BINARY(:%s)';
        } else {
            $clause = '%s.%s %s :%s';
        }

        $or = $query->getQueryBuilder()->expr()->orX(
            sprintf($clause, $alias, $field, $operator, $parameterName)
        );

        if (StringOperatorType::TYPE_NOT_CONTAINS === $type || StringOperatorType::TYPE_NOT_EQUAL === $type) {
            $or->add($query->getQueryBuilder()->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $this->applyWhere($query, $or);

        switch ($type) {
            case StringOperatorType::TYPE_EQUAL:
            case StringOperatorType::TYPE_NOT_EQUAL:
                $format = '%s';
                break;
            case StringOperatorType::TYPE_STARTS_WITH:
                $format = '%s%%';
                break;
            case StringOperatorType::TYPE_ENDS_WITH:
                $format = '%%%s';
                break;
            default:
                // NEXT_MAJOR: Remove this line, uncomment the following and remove the deprecation
                $format = $this->getOption('format');
                // $format = '%%%s%%';

                if ('%%%s%%' !== $format) {
                    @trigger_error(
                        'The "format" option is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will be removed in version 4.0.',
                        \E_USER_DEPRECATED
                    );
                }
        }

        $query->getQueryBuilder()->setParameter(
            $parameterName,
            sprintf(
                $format,
                false !== $caseSensitive || '' === $data['value'] ? $data['value'] : mb_strtolower($data['value'])
            )
        );
    }

    public function getDefaultOptions()
    {
        return [
            // NEXT_MAJOR: Remove the format option.
            'format' => '%%%s%%',
            'case_sensitive' => null,
            'trim' => self::TRIM_BOTH,
            'allow_empty' => false,
        ];
    }

    public function getRenderSettings()
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            // NEXT_MAJOR: Throw an \OutOfRangeException instead.
            @trigger_error(sprintf(
                'Passing a non supported type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw an %s exception in version 4.0.',
                \OutOfRangeException::class
            ), \E_USER_DEPRECATED);
//            throw new \OutOfRangeException(sprintf(
//                'The type "%s" is not supported, allowed one are "%s".',
//                $type,
//                implode('", "', array_keys(self::CHOICES))
//            ));
        }

        // NEXT_MAJOR: Remove the default value
        return self::CHOICES[$type] ?? self::CHOICES[StringOperatorType::TYPE_CONTAINS];
    }

    private function trim(string $string): string
    {
        $trimMode = $this->getOption('trim');

        if ($trimMode & self::TRIM_LEFT) {
            $string = ltrim($string);
        }

        if ($trimMode & self::TRIM_RIGHT) {
            $string = rtrim($string);
        }

        return $string;
    }
}
