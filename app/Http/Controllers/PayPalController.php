<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    // Set the user for authentication
    public function __construct() {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();
            return $next($request);
        });
    }

    public function processTransaction(Request $request)
    {
        // Set the API credentials
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        // Create an order
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('success-transaction'),
                "cancel_url" => route('cancel-transaction'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->price
                    ]
                ]
            ]
        ]);
    
        // Insert the data into the Bookings and Transactions tables
        if (isset($response['id']) && $response['id'] != null) {
            $booking_data = [
                'user_id' => $this->user->id,
                'product_id' => $request->id,
                'paypal_order_id' => $response['id'],
                'price' => $request->price,
                'currency' => 'usd',
                'created_at' => Carbon::now(),
            ];

            $booking = Bookings::insertGetId($booking_data);
            $trxn_data = [
                'paypal_order_id' => $response['id'],
                'booking_id' => $booking,
                'user_id' => $this->user->id,
                'product_id' => $request->id,
                'payment_status' => $response['status'],
                'created_at' => Carbon::now(),
            ];

            Transactions::insert($trxn_data);
            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
            return redirect()
                ->route('dashboard')
                ->with('fail', 'Something went wrong.');
        } else {
            return redirect()
                ->route('dashboard')
                ->with('fail', $response['message'] ?? 'Something went wrong.');
        }
    
    }

    public function successTransaction(Request $request)
    {
        // Set the API credentials
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        // Capture the payment order using the given token
        $response = $provider->capturePaymentOrder($request['token']);
        $capture = $response['purchase_units'][0]['payments']['captures'][0];
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            // Insert the data into the Bookings and Transactions tables
            
            $trxn_data = [
                'paypal_url' => $response['links'][0]['href'],
                'paid_amount' => $capture['amount']['value'],
                'currency' => $capture['amount']['currency_code'],
                'payment_status' => $response['status'],
                'updated_at' => Carbon::now(),
            ];

            Transactions::where('paypal_order_id', '=', $response['id'])->update($trxn_data);

            $booking_data = [
                'status' => strtolower($response['status']),
                'updated_at' => Carbon::now(),
            ];
            Bookings::where('paypal_order_id', '=', $response['id'])->update($booking_data);
            return redirect()
                ->route('dashboard')
                ->with('success', 'Transaction Complete.');
        } else {
            $trxn_data = [
                'status' => $response['status'],
                'updated_at' => Carbon::now(),
            ];

            Transactions::where('paypal_order_id', '=', $response['id'])->update($trxn_data);

            $booking_data = [
                'status' => strtolower($response['status']),
                'updated_at' => Carbon::now(),
            ];
            Bookings::where('paypal_order_id', '=', $response['id'])->update($booking_data);
            return redirect()
                ->route('dashboard')
                ->with('fail', $response['message'] ?? 'Something went wrong.');
        }
    }

    public function cancelTransaction(Request $request)
    {
        $trxn_data = [
            'payment_status' => 'CANCELED',
            'updated_at' => Carbon::now(),
        ];
        
        Transactions::where('paypal_order_id', '=', $request['token'])->update($trxn_data);

        $booking_data = [
            'status' => 'completed',
            'updated_at' => Carbon::now(),
        ];

        Bookings::where('paypal_order_id', '=', $request['token'])->update($booking_data);

        return redirect()
            ->route('dashboard')
            ->with('fail', $response['message'] ?? 'You have canceled the transaction.');
    }

}
