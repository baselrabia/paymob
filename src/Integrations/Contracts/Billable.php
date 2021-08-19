<?php

namespace Basel\PayMob\Integrations\Contracts;

interface Billable
{
    public function getBillingData(): array;
}
