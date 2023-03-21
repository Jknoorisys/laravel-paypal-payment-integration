<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use Illuminate\Http\Request;

use App\Models\Products;
use App\Models\Transactions;
use App\Models\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    private $provider;

    public function __construct() {
        // Multiligual
        $lang = (isset($_POST['language']) && !empty($_POST['language'])) ? $_POST['language'] : 'en';
        App::setlocale($lang);

        // Set the API credentials
        $this->provider = new PayPalClient;
        $this->provider->setApiCredentials(config('paypal'));
        $paypalToken = $this->provider->getAccessToken();
    }

    public function payment(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'user_id'   => 'required||numeric',
            'product_id' =>'required',
            'unit_amount'     => 'required',
            'quantity'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try {
            $user_id = $request->user_id;
            $product_id = $request->product_id;
            $unit_amount = $request->unit_amount;
            $quantity = $request->quantity;

            // Fetch a Product object based on the given 'product_id' from the 'Products' model
            $product = Products::where('id', '=', $product_id)->first();
            
            // if product does not exists, throw an erorr
            if (!$product || empty($product)) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.order.invalid'),
                ],400);
            }
          
            // Fetch a user based on the given 'user_id' from the 'Users' model
            $user = User::where('id', '=', $user_id)->first();

            // if user does not exists, throw an erorr
            if (!$user || empty($user)) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.order.not-found'),
                ],400);
            }

            // Set the success and cancel URLs for the checkout session
            $return_url = url('api/paypal/paymentSuccess');
            $cancel_url = url('api/paypal/paymentFail');

            // Create an order
            $response = $this->provider->createOrder([
                "intent" => "AUTHORIZE",
                "application_context" => [
                    "return_url" => $return_url,
                    "cancel_url" => $cancel_url,
                ],
                "purchase_units" => [
                    0 => [
                        'invoice_id' => (string)rand(10000, 20000),
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => $unit_amount * $quantity,
                            "breakdown" => [
                                "item_total" => [
                                    "currency_code" => "USD",
                                    "value" => $unit_amount * $quantity,
                                ],
                            ],
                        ],
                        "items" => [
                            [
                                "name" => $product->name,
                                "description" => "Product description",
                                "unit_amount" => [
                                    "currency_code" => "USD",
                                    "value" => $unit_amount,
                                ],
                                "quantity" => $quantity,
                            ],
                        ],
                    ],
                ],
            ]);

            if (isset($response['id']) && $response['id'] != null) {
                $booking_data = [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'order_id' => $response['id'],
                    'product_price' => $product->price,
                    'currency' => 'USD',
                    'quantity' => $quantity,
                    'amount_paid' => $unit_amount * $quantity,
                    'created_at' => Carbon::now(),
                ];

                // Insert the data into the Bookings and Transactions tables
                $booking_id = Bookings::insertGetId($booking_data);
                
                if($booking_id){
                    return response()->json([
                        'status'    => 'success',
                        'message'   => trans('msg.paypal.order.success'),
                        'data'      => $response['links'][1]
                    ],200);
                }else{
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.paypal.order.failure'),
                    ],400);
                }
            } else {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.order.failure'),
                ],400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'token'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try {
            $token = $request->token;

            // get booking details from database
            $booking = Bookings::where('order_id', '=', $token)->first();

            // get orer details from paypal using order_id (token)
            $order = $this->provider->showOrderDetails($token);
            if (!empty($order) && $order['status'] == 'APPROVED') {
                // Authorize Payment for Order
                $auth = $this->provider->authorizePaymentOrder($token);

                $auth_id = $auth['purchase_units'][0]['payments']['authorizations'][0]['id'];
                $invoice_id = $auth['purchase_units'][0]['payments']['authorizations'][0]['invoice_id'];
                $amount_val = $auth['purchase_units'][0]['payments']['authorizations'][0]['amount']['value'];

                // Capture Authorized Payment
                $payment = $this->provider->captureAuthorizedPayment($auth_id, $invoice_id, floatval($amount_val), 'CAPTURE');

                // get orer details from paypal using order_id (token)
                $response = $this->provider->showOrderDetails($token);
                $item = $response['purchase_units'][0]['items'];

                // update booking status
                $booking_data = [
                    'status' => 'completed',
                    'updated_at' => Carbon::now(),
                ];
                Bookings::where('order_id', '=', $response['id'])->update($booking_data);

                // Insert the data into the Transactions table
                $trxn_data = [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'order_id' => $booking->order_id,
                    'paypal_url' => $response['links'][0]['href'],
                    'product_id' => $booking->product_id,
                    'product_price' => $item[0]['unit_amount']['value'],
                    'quantity' => $item[0]['quantity'],
                    'amount_paid' => $amount_val,
                    'currency' => $booking->currency,
                    'payment_status' => $response['status'],
                    'created_at' => Carbon::now(),
                ];
    
                $trxn = Transactions::insertGetId($trxn_data);

                // send invoice to customer
                $user = User::find($booking->user_id);

                $invoice_data = [
                    'trxn_id' => $trxn,
                    'invoice_number' => $invoice_id,
                    'user_name' => $user ? $user->name : '',
                    'user_email' => $user ? $user->email : '',
                    'product_name' => $item[0]['name'],
                    'product_price' => $item[0]['unit_amount']['value'],
                    'quantity' => $item[0]['quantity'],
                    'amount_paid' => $amount_val,
                    'currency' => $booking->currency,
                    'date' => Carbon::now()->format('d.m.Y')
                ];

               generateInvoicePdf($invoice_data);

                // update user_type in users table
                $user_data = [
                    'user_type'   => 'paid',
                    'updated_at'  => Carbon::now(),
                ];
                User::where('id', '=', $booking->user_id)->update($user_data);

                return response()->json([
                    'status'    => 'success',
                    'message'   => trans('msg.paypal.success'),
                ],200);
            }elseif (!empty($order) && $order['status'] == 'COMPLETED') {

                // check authorization status
                $auth_status = $order['purchase_units'][0]['payments']['authorizations'][0]['status'];
                if ($auth_status == 'CAPTURED') {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.paypal.paid'),
                    ],200);
                }else{
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.paypal.invalid'),
                    ],200);
                }
            }elseif (!empty($order) && $order['status'] == 'CREATED') {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.failure'),
                ],200);
            }else{
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.invalid'),
                ],200);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }

    public function paymentFail(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'token'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try {
            $token = $request->token;

            // get booking details from database
            $booking = Bookings::where('order_id', '=', $token)->first();

            // get orer details from paypal using order_id (token)
            $order = $this->provider->showOrderDetails($token);
            
            if (!empty($order) && $order['status'] == 'APPROVED') {

                // Authorize Payment for Order
                $auth = $this->provider->authorizePaymentOrder($token);

                $auth_id = $auth['purchase_units'][0]['payments']['authorizations'][0]['id'];

                // Void/Cancel Authorized Payment
                $payment = $this->provider->voidAuthorizedPayment($auth_id);

                $trxn_data = [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'order_id' => $booking->order_id,
                    'paypal_url' => $order['links'][0]['href'],
                    'product_id' => $booking->product_id,
                    'product_price' => $booking->product_price,
                    'quantity' => $booking->quantity,
                    'amount_paid' => $order['purchase_units'][0]['amount']['value'],
                    'currency' => $order['purchase_units'][0]['amount']['currency_code'],
                    'payment_status' => 'FAILED',
                    'created_at' => Carbon::now(),
                ];
    
                $trxn = Transactions::insertGetId($trxn_data);
    
                $booking_data = [
                    'status' => 'completed',
                    'updated_at' => Carbon::now(),
                ];

                Bookings::where('order_id', '=', $booking->order_id)->update($booking_data);

                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.failure'),
                ],400);
            }elseif (!empty($order) && $order['status'] == 'COMPLETED') {
                // check authorization status
                $auth_status = $order['purchase_units'][0]['payments']['authorizations'][0]['status'];
                if ($auth_status == 'CAPTURED') {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.paypal.paid'),
                    ],200);
                }
            }elseif (!empty($order) && $order['status'] == 'CREATED') {
                $trxn_data = [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'order_id' => $booking->order_id,
                    'paypal_url' => $order['links'][0]['href'],
                    'product_id' => $booking->product_id,
                    'product_price' => $booking->product_price,
                    'quantity' => $booking->quantity,
                    'amount_paid' => $order['purchase_units'][0]['amount']['value'],
                    'currency' => $order['purchase_units'][0]['amount']['currency_code'],
                    'payment_status' => 'FAILED',
                    'created_at' => Carbon::now(),
                ];
    
                $trxn = Transactions::insertGetId($trxn_data);
    
                $booking_data = [
                    'status' => 'completed',
                    'updated_at' => Carbon::now(),
                ];

                Bookings::where('order_id', '=', $booking->order_id)->update($booking_data);

                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.failure'),
                ],200);
            }else{
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.paypal.invalid'),
                ],200);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }
}
