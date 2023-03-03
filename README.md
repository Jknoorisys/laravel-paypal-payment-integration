<h1>Laravel Paypal Payment Integration</h1>
<h4>Follow the steps below:</h4>
<ul>
    <li> Create New Project </li>
    <li> Install Packages for Paypal Payment Gateway Using Composer </li>
    <li> Create PayPal account </li>
    <li> Configure the package </li>
    <li> Create Routes </li>
    <li> Create blade file to create payment button </li>
    <li> Run the app </li>
</ul>

<ol>
    <li>Create a new project</li>
    <p>Create a new project with the command as below.</p>
    <p>composer create-project laravel/laravel-paypal-integration --jet</p>
    <p>After the new project has been created, go to your project directory.</p>
    <p>cd paypal</p>
    
    <li>Install Packages for Paypal Payment Gateway Using Composer</li>
    <p>Run the following command.</p>
    <p>composer require srmklive/paypal:~3.0</p>
</ol>


3. Create PayPal credentials
After installing paypal package, we need client_id and secret_key for paypal integration, so we need to enter in paypal developer mode and create new sandbox account for the same. After login in paypal you need to get the client_id and secret_key as shown below. before getting client_id and secret_key we need to create application. So, check the screenshot below and build an app. Login to the Developer Dashboard.

Click Create Apps.

<img src="https://miro.medium.com/v2/resize:fit:720/0*7ZGcg-bK_Q0ug28T" alt="img">
Fill in the name of the application that was created.


Then you will get the client key and secret key that will be used in the application.


4. Configure the package
After the package installation is complete, you open your project and add the key and secret key that you got in the .env file.

PAYPAL_MODE=sandbox
#Paypal sandbox credential
PAYPAL_SANDBOX_CLIENT_ID=AXELAz06GFLR.............................QNu7zyjuYpFLu1g
PAYPAL_SANDBOX_CLIENT_SECRET=EA9dinW1.............................PUzgVQCz7fK4tqe1-jLZCyHzZ0tDTRAx-6qJdIY933Q
If you want to customize the package’s default configuration options, run the vendor:publish command below.

php artisan vendor:publish --provider "Srmklive\PayPal\Providers\PayPalServiceProvider"

This will create a configuration file config/paypal.php with the details below, which you can modify.


5. Create Routes
Now we need to create an application route that we will test the application test transaction on. Open the route/web.php application route file and add the following new route.

<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayPalController;
Route::get('create-transaction', [PayPalController::class, 'createTransaction'])->name('createTransaction');
Route::get('process-transaction', [PayPalController::class, 'processTransaction'])->name('processTransaction');
Route::get('success-transaction', [PayPalController::class, 'successTransaction'])->name('successTransaction');
Route::get('cancel-transaction', [PayPalController::class, 'cancelTransaction'])->name('cancelTransaction');
Create Controller
After we create a route, then next we create a controller using php artisan.

php artisan make:controller PayPalController
We already have a controller in the directory app/Http/Controllers/PayPalController.php. Open it and add the code below.

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
class PayPalController extends Controller
{
    /**
     * create transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function createTransaction()
    {
        return view('transaction');
    }
    /**
     * process transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function processTransaction(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('successTransaction'),
                "cancel_url" => route('cancelTransaction'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => "1000.00"
                    ]
                ]
            ]
        ]);
        if (isset($response['id']) && $response['id'] != null) {
            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
            return redirect()
                ->route('createTransaction')
                ->with('error', 'Something went wrong.');
        } else {
            return redirect()
                ->route('createTransaction')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }
    /**
     * success transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function successTransaction(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return redirect()
                ->route('createTransaction')
                ->with('success', 'Transaction complete.');
        } else {
            return redirect()
                ->route('createTransaction')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }
    /**
     * cancel transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancelTransaction(Request $request)
    {
        return redirect()
            ->route('createTransaction')
            ->with('error', $response['message'] ?? 'You have canceled the transaction.');
    }
}
6. Create blade file to create payment button
We are going to create a view that will direct to process the transaction. Create blade view resources/views/transaction.blade.php file and add below code to it.

7. Run the app
Paypal integration complete. Now we need to make a transaction. Run the Laravel server using the Artisan command below.

php artisan serve
Open it with a url like below. To pay with a PayPal account, you need to create an account create sandbox account.

http://localhost:8000/create-transaction

click Pay, it will display the payment form.


Thus this tutorial I provide, hopefully useful.

Thanks.
