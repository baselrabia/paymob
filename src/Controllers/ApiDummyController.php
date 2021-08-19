<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Basel\PayMob\Facades\PayMob;
use Basel\PayMob\Integrations\CreditCard;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Validator;  

class ApiDummyController extends Controller
{
    // /**
    //  * show the order details to the user.
    //  *
    //  * @return \Illuminate\View\View
    //  */
    // public function checkOut()
    // {
    //     $order = Order::find(1);
    //     $payment_types= ['CreditCard'=>'Credit Card'];
    //     return view('paymob::checkout')->with('order', $order)->with('payment_types', $payment_types);
    // }

    /**
     * process the order on the gateway side.
     *
     * @return \Illuminate\View\View
     */
    public function process(Request $request)
    {
        // $request->validate([
        //    
        // ]);

        $validation =  Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_type' => 'nullable|string',
        ]);

        if ($validation->fails()) {
            return $this->ApiValidator($validation);
        }

        $order = Order::find($request->order_id);

        //$payment_type = $request->payment_type ; //"CreditCard";

        $user         = auth('student')->user();
        $total        = $order->price; // order total
 
        try {
            $link =  (new CreditCard($user,$order->id))->checkOut($total); // or MobileWallet, etc..
            $order->update($link);
            return $link;

        
        } catch (RequestException $e) {
            
 
            if ($e->getCode() == "422" && stripos($e->getMessage(), 'duplicate') !== false) {
                
                return ['paymob_link' => $order->paymob_link];

            }
            return $this->ApiCatchMsg($e);
        }
    }

    /**
     * validate and complete the order.
     *
     * https://acceptdocs.paymobsolutions.com/docs/transaction-callbacks#transaction-response-callback.
     */
    public function complete(Request $request)
    {
        try {
            PayMob::validateHmac($request->hmac, $request->id);
        } catch (RequestException $e) {
            return $this->ApiCatchMsg($e);
        }
        // save the transaction data to our server
        $data = $request->all();
          
        $orderId = $request['order'];
        $order   = Order::wherePaymobOrderId($orderId)->first();

         Payment::create([
            'order_id' => $order->id,
            'student_id'=> $order->student_id,
            'data_message' => $data['data_message'],
            'response' => json_encode($data),
            'amount_cents' => $data['amount_cents'] / 100,
            'currency' => $data['currency'],
         ]);
        //dd($order);
        // Statuses.
        $isSuccess  =  $request['success'] == 'false' ? false : true;
        $isVoided   =  $request['is_voided']== 'false' ? false : true;
        $isRefunded =  $request['is_refunded']== 'false' ? false : true;

 
        if ($isSuccess && !$isVoided && !$isRefunded) { // transcation succeeded.
            $this->succeeded($order,);
        } elseif ($isSuccess && $isVoided) { // transaction voided.
            $this->voided($order);
        } elseif ($isSuccess && $isRefunded) { // transaction refunded.
            $this->refunded($order);
        } elseif (!$isSuccess) { // transaction failed.
            $this->failed($order);
        }

     }
 

    /**
     * Transaction succeeded.
     *
     * @param  object  $order
     * @return void
     */
    protected function succeeded($order)
    {
        echo 'succeeded' ;
    }

    /**
     * Transaction voided.
     *
     * @param  object  $order
     * @return void
     */
    protected function voided($order)
    {
        # code...
        echo 'voided';

    }

    /**
     * Transaction refunded.
     *
     * @param  object  $order
     * @return void
     */
    protected function refunded($order)
    {
        # code...
        echo 'refunded';

    }

    /**
     * Transaction failed.
     *
     * @param  object  $order
     * @return void
     */
    protected function failed($order)
    {
        # code...
        echo 'failed';

    }

    function ApiValidator($validator)
    {
        return response()->json(
            [
                'status' => 0,
                'data' => $validator->errors(),
                'code' => 400,
                'message' => $validator->errors()->first(),
            ],
            200,
            []
        );
    }

    function ApiCatchMsg($e)
    {
        return response()->json(
            [
                'status' => 0,
                'data' => $e->getMessage(),
                'code' => 400,
                'message' => __('something went wrong, please try again later'),
            ],
            200,
            []
        );
    }


}
