<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use App\Mail\SendMail;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailNotify;
use Illuminate\Support\Facades\Http;

use Stripe\Customer;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Exception\CardException;
use Stripe\StripeClient;


class PayController extends BaseController
{

    private $stripe;
    public function __construct()
    {
        $this->stripe = new StripeClient("sk_test_51HoqVpCLraJWn74O6nWB3bSmzO40ikez0uiQYZyDpqo2yYUHQCUHoDcUqT9oLbySjVll7GfQv4QiCZtAMERsMTh300kUWq1NvG");
    }
    
    public function checkCard(Request $request)
    {
        try {
        $token = null;
        $token =  $this->stripe->tokens->create([
            'card' => [
                'number' => $request->number,
                'exp_month' =>$request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ],
        ]);

        $data["token"] = $token;
         

        } 
        catch (\Exception $ex) {
             return $ex->getMessage();
         }


        return $data;

    }


    public function makePayement(Request $request)
    {
        try {
            Stripe::setApiKey('sk_test_51HoqVpCLraJWn74O6nWB3bSmzO40ikez0uiQYZyDpqo2yYUHQCUHoDcUqT9oLbySjVll7GfQv4QiCZtAMERsMTh300kUWq1NvG');
    
            $customer = Customer::create(array(
                'email' => $request->email,
                'source'  => $request->token
            ));
    
            $price = $request->price;
            $amount = $price*100;
            $charge = Charge::create(array(
                'customer' => $customer->id,
                'amount'   => $amount,
                'currency' => 'eur'
            ));
            return response()->json([
                'charge' => $charge,
                'status' => 'success',
                'message' => 'Votre carte a été débitée avec succès'
            ]);
    
    
            } catch (\Exception $ex) {
               return $ex->getMessage();
               return response()->json([
                'charge' => $ex->getMessage(),
                'status' => 'error',
                'message' => 'Votre carte n\'a été débitée'
            ]);
            }
    
    
    }



    
    
    
    
    
    
}

