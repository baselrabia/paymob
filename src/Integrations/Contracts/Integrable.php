<?php

namespace Basel\PayMob\Integrations\Contracts;

interface Integrable
{
    public function getPaymentTypeName(): string;
}
