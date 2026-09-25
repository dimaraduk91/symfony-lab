<?php

declare(strict_types=1);

namespace App\Ordering\Presentation\Http\Controller;

use App\Ordering\Application\Query\SearchOrders\SearchOrderResult;
use App\Ordering\Application\Query\SearchOrders\SearchOrders;
use App\Ordering\Application\Query\SearchOrders\SearchOrdersHandler;
use App\Ordering\Domain\Model\OrderStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SearchOrdersAction
{
    private const int DEFAULT_LIMIT = 50;
    private const int MAX_LIMIT = 100;

    public function __construct(private SearchOrdersHandler $handler)
    {
    }

    #[Route('/api/orders/search', name: 'search_orders', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $query = $this->mapQuery($request);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $orders = $this->handler->handle($query);

        return new JsonResponse([
            'items' => array_map($this->serialize(...), $orders),
            'limit' => $query->limit,
        ]);
    }

    private function mapQuery(Request $request): SearchOrders
    {
        $statusValue = $this->optionalString($request, 'status');
        $status = $statusValue === null ? null : OrderStatus::tryFrom($statusValue);
        if ($statusValue !== null && $status === null) {
            throw new \InvalidArgumentException(sprintf(
                'status must be one of: %s.',
                implode(', ', array_column(OrderStatus::cases(), 'value')),
            ));
        }

        $customerEmail = $this->optionalString($request, 'customerEmail');
        if ($customerEmail !== null && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('customerEmail must be a valid email address.');
        }

        $sellerId = $this->optionalString($request, 'sellerId');
        $from = $this->optionalDate($request, 'from');
        $to = $this->optionalDate($request, 'to');
        if ($from !== null && $to !== null && $from > $to) {
            throw new \InvalidArgumentException('from must be earlier than or equal to to.');
        }

        $limitValue = $request->query->get('limit', (string) self::DEFAULT_LIMIT);
        if (!is_string($limitValue) || filter_var($limitValue, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('limit must be an integer.');
        }

        $limit = (int) $limitValue;
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new \InvalidArgumentException(sprintf('limit must be between 1 and %d.', self::MAX_LIMIT));
        }

        return new SearchOrders(
            status: $status,
            customerEmail: $customerEmail,
            sellerId: $sellerId,
            from: $from,
            toExclusive: $to?->modify('+1 day'),
            limit: $limit,
        );
    }

    private function optionalString(Request $request, string $name): ?string
    {
        $value = $request->query->get($name);
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $name));
        }

        return trim($value);
    }

    private function optionalDate(Request $request, string $name): ?\DateTimeImmutable
    {
        $value = $this->optionalString($request, $name);
        if ($value === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException(sprintf('%s must be a valid date in YYYY-MM-DD format.', $name));
        }

        return $date;
    }

    /** @return array<string, int|string> */
    private function serialize(SearchOrderResult $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'customerId' => $order->customerId,
            'customerEmail' => $order->customerEmail,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'createdAt' => $order->createdAt->format(DATE_ATOM),
            'updatedAt' => $order->updatedAt->format(DATE_ATOM),
        ];
    }
}
