<h1>Laravel Paypal Payment Integration</h1>

h4>Steps to clone laravel project</h4>
<ul>
    <li>Clone your project</li>
    <li>Go to the folder application using cd command on your cmd or terminal</li>
    <li>Run composer install on your cmd or terminal</li>
    <li>Copy .env.example file to .env in the root folder using command <i> cp .env.example .env </i></li>
    <li>Open your .env file and change the database name (DB_DATABASE) to whatever you have, username (DB_USERNAME) and password (DB_PASSWORD) field correspond to your configuration.</li>
    <li>Run php artisan key:generate</li>
    <li>Run php artisan migrate</li>
    <li>Run php artisan serve</li>
    <li>Go to http://localhost:8000/</li>
</ul>

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
    <li><h5>Create a new project</h5></li>
        <p>Create a new project with the command as below.</p>
        <p><i>composer create-project laravel/laravel-paypal-integration --jet</i></p>
        <p>After the new project has been created, go to your project directory.</p>
        <p><i>cd paypal</i></p>
    <li><h5>Install Packages for Paypal Payment Gateway Using Composer</h5></li>
        <p>Run the following command.</p>
        <p><i>composer require srmklive/paypal:~3.0</i></p>
    <li><h5>Create PayPal Account</h5></li>
        <p>After installing paypal package, we need client_id and secret_key for paypal integration, so we need to enter in paypal developer mode and create new sandbox account for the same. After login in paypal you need to get the client_id and secret_key as shown below. before getting client_id and secret_key we need to create application. So, check the screenshot below and build an app. Login to the Developer Dashboard.</p>
        <p>Click Create Apps.</p>
        <img src="https://miro.medium.com/v2/resize:fit:720/0*7ZGcg-bK_Q0ug28T" alt="img">
        <p>Fill in the name of the application that was created.</p>
        <img src="https://miro.medium.com/v2/resize:fit:720/0*QdHZbMTM4hnxHtkk" alt="img">
        <p>Then you will get the client key and secret key that will be used in the application.</p>
        <img src="https://miro.medium.com/v2/resize:fit:720/0*s0MdGxcaTC4mo2ys" alt="img">    
    <li><h5>Configure the package</h5></li>
        <p>After the package installation is complete, you open your project and add the key and secret key that you got in the .env file.</p>
        <p>
        PAYPAL_MODE=sandbox<br>
        PAYPAL_SANDBOX_CLIENT_ID=AXELAz06GFLR.............................QNu7zyjuYpFLu1g<br>
        PAYPAL_SANDBOX_CLIENT_SECRET=EA9dinW1.............................PUzgVQCz7fK4tqe1-jLZCyHzZ0tDTRAx-6qJdIY933Q
        </p>
        <p>If you want to customize the package’s default configuration options, run the vendor:publish command below.</p>
        <p><i>php artisan vendor:publish --provider "Srmklive\PayPal\Providers\PayPalServiceProvider"</i></p>
        <img src="https://miro.medium.com/v2/resize:fit:640/0*78fimJBrscB_gjQx" alt="img">
        <p>This will create a configuration file config/paypal.php with the details below, which you can modify.</p>
        <img src="https://miro.medium.com/v2/resize:fit:720/0*KZjimfTUs0el7ZWL" alt="img">
    <li><h5>Create Routes</h5></li>
        <p>Now we need to create an application route that we will test the application test transaction on. Open the route/web.php application route file and add the new routes</p>
        <p>Create Controller</p>
        <p><i>php artisan make:controller PayPalController</i></p>
        <p>Run database migrations</p>
        <p><i>php artisan migrate</i></p>
        <p>Run database seeder</p>
        <p><i>php artisan db:seed --class=ProductsSeeder</i></p>
    <li><h5>Create blade file to create payment button</h5></li>
        <p>create a view that will direct to process the transaction. Create blade view resources/views/transaction.blade.php file</p>
    <li><h5>Run the app</h5></li>
        <p>Paypal integration complete. Now we need to make a transaction. Run the Laravel server using the Artisan command below.</p>
        <p><i>php artisan serve</i></p>
    <p>Thus this tutorial I provide, hopefully useful.</p>
    <p>Thanks.</p>  
</ol>





