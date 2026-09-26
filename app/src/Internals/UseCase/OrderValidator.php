<?php

namespace App\Internals\UseCase;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface OrderValidator
{
    public function validate(FakeOrderEvent $order): void;
}
