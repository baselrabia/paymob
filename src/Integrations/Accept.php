<?php

namespace Basel\PayMob\Integrations;

use Basel\PayMob\PayMob;
use Illuminate\Support\Facades\Http;
use Basel\PayMob\Integrations\Contracts\Billable;
use Basel\PayMob\Integrations\Contracts\Integrable;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class Accept extends PayMob implements Integrable
{
    protected $user;
    protected $merchant_order_id;
    protected $payment_token;

    /**
     * @param Billable|Authenticatable $user
     */
    public function __construct($user, $merchant_order_id)
    {
        parent::__construct();

        $this->user = $user;
        $this->merchant_order_id = $merchant_order_id;
    }

    /* -------------------------------------------------------------------------- */
    /*                                   BEFORE                                   */
    /* -------------------------------------------------------------------------- */

    protected function getPaymentTypeConfig($key)
    {
        return "payment_types.{$this->getPaymentTypeName()}.$key";
    }

    /* -------------------------------------------------------------------------- */

    /**
     * 2. https://acceptdocs.paymobsolutions.com/docs/accept-standard-redirect#2-order-registration-api.
     *
     * @param array     $items
     * @param float|int $total
     */
    protected function orderRegistration($items, $total)
    {
        $response = Http::withToken($this->auth_token)
            ->post($this->getConfigKey('url.order'), [
                'delivery_needed' => $this->getConfigKey('delivery_needed'),
                'amount_cents'    => $this->getAmountInCents($total),
                'currency'        => $this->getCurrency(),
                "merchant_order_id" => $this->merchant_order_id,
                'items'           => $items,
            ])
            ->throw();

        return $response['id'];
    }

    /**
     * 3. https://acceptdocs.paymobsolutions.com/docs/accept-standard-redirect#3-payment-key-request.
     */
    protected function getDefaultBillingData()
    {
        return [
            'building'        => 'NA',
            'floor'           => 'NA',
            'apartment'       => 'NA',
            'shipping_method' => 'PKG',
            'country'         => 'EG',
            'postal_code'     => 'NA',
            'city'            => 'Cairo',
            'state'           => 'Cairo',
        ];
    }

    protected function getIntegrationId()
    {
        return $this->getConfigKey($this->getPaymentTypeConfig('integration_id'));
    }

    /**
     * @param mixed     $order_id
     * @param float|int $total
     */
    protected function paymentKeyRequest($order_id, $total)
    {
        $billing_data = array_merge(
            $this->getDefaultBillingData(),
            $this->user->getBillingData()
        );

        $response = Http::withToken($this->auth_token)
            ->post($this->getConfigKey('url.payment_key'), [
                'amount_cents'         => $this->getAmountInCents($total),
                'expiration'           => $this->getConfigKey('exp_after') * 1000, // milliseconds
                'order_id'             => $order_id,
                'billing_data'         => $billing_data,
                'currency'             => $this->getCurrency(),
                'integration_id'       => $this->getIntegrationId(),
                'lock_order_when_paid' => true,
            ])
            ->throw();

        return $response['token'];
    }

    /* -------------------------------------------------------------------------- */

    abstract public function checkOut($total, $items = []);
}
