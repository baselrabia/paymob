<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Basel\PayMob\Facades\PayMob;
use Basel\PayMob\Integrations\CreditCard;
use Illuminate\Http\Client\RequestException;

class DummyController extends Controller
{
    /**
     * show the order details to the user.
     *
     * @return \Illuminate\View\View
     */
    public function checkOut()
    {
        return view('paymod::checkout');
    }

    /**
     * process the order on the gateway side.
     *
     * @return \Illuminate\View\View
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_type' => [
                'required',
                'string',
            ],
        ]);

        $payment_type = $request->payment_type;
        $user         = $request->user();
        $total        = 0; // order total

        try {
            return (new CreditCard($user))->checkOut($total); // or MobileWallet, etc..
        } catch (RequestException $e) {
            return __('something went wrong, please try again later');
        }
    }

    /**
     * validate and complete the order.
     *
     * https://acceptdocs.paymobsolutions.com/docs/transaction-callbacks#transaction-response-callback.
     */
    public function complete(Request $request)
    {
        PayMob::validateHmac($request->hmac, $request->id);

        // save the transaction data to our server
        $data = $request->all();

        return view('paymod::complete');
    }
}
