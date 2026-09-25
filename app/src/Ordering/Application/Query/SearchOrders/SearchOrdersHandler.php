<?php

declare(strict_types=1);

namespace App\Ordering\Application\Query\SearchOrders;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;

final readonly class SearchOrdersHandler
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return list<SearchOrderResult> */
    public function handle(SearchOrders $query): array
    {
        $conditions = [];
        $parameters = ['limit' => $query->limit];
        $types = ['limit' => ParameterType::INTEGER];

        if ($query->status !== null) {
            $conditions[] = 'o.status = :status';
            $parameters['status'] = $query->status->value;
        }

        if ($query->customerEmail !== null) {
            $conditions[] = 'c.email = :customerEmail';
            $parameters['customerEmail'] = $query->customerEmail;
        }

        if ($query->sellerId !== null) {
            $conditions[] = <<<'SQL'
                EXISTS (
                    SELECT 1
                    FROM order_items oi
                    WHERE oi.order_id = o.id AND oi.seller_id = :sellerId
                )
                SQL;
            $parameters['sellerId'] = $query->sellerId;
        }

        if ($query->from !== null) {
            $conditions[] = 'o.created_at >= :from';
            $parameters['from'] = $query->from;
            $types['from'] = Types::DATETIMETZ_IMMUTABLE;
        }

        if ($query->toExclusive !== null) {
            $conditions[] = 'o.created_at < :toExclusive';
            $parameters['toExclusive'] = $query->toExclusive;
            $types['toExclusive'] = Types::DATETIMETZ_IMMUTABLE;
        }

        $where = $conditions === [] ? '' : 'WHERE '.implode(' AND ', $conditions);
        $sql = <<<SQL
            SELECT
                o.id,
                o.status,
                o.customer_id,
                c.email AS customer_email,
                o.amount,
                o.currency,
                o.created_at,
                o.updated_at
            FROM orders o
            INNER JOIN customers c ON c.id = o.customer_id
            {$where}
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT :limit
            SQL;

        $rows = $this->connection->executeQuery($sql, $parameters, $types)->fetchAllAssociative();

        return array_map(
            static fn (array $row): SearchOrderResult => new SearchOrderResult(
                id: (string) $row['id'],
                status: (string) $row['status'],
                customerId: (string) $row['customer_id'],
                customerEmail: (string) $row['customer_email'],
                amount: (int) $row['amount'],
                currency: (string) $row['currency'],
                createdAt: new \DateTimeImmutable((string) $row['created_at']),
                updatedAt: new \DateTimeImmutable((string) $row['updated_at']),
            ),
            $rows,
        );
    }
}
