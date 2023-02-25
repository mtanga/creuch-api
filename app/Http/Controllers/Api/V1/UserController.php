<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
 
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
class UserController extends BaseController
{
    
    
    
    public function check()
    {
     return "Api Connected"; 
    }


    public function checkmatricule(Request $request)
    {
        $data["matricule"] = DB::select( DB::raw("select * from ps_cart_rule where matricule = :request"), array('request' =>$request->matricule));
        if($data["matricule"]){
            $data["customer"] = DB::select( DB::raw("select * from ps_customer where matricule = :requests"), array('requests' =>$request->matricule));
            if($data["customer"]){
                $data["result"] = "201";
            }
            else{
                $data["result"] = "202"; 
            }
        }
        else{
            $data["result"] = "404";
        }
        return $data;
        
    }


    public function checkcode(Request $request)
    {
        
        $query = 'select t1.id_seller, t2.id_customer from ps_kb_mp_custom_field_seller_mapping t1 inner join ps_kb_mp_seller t2 on t1.id_seller = t2.id_seller where t1.value="'.$request->code.'" AND t1.id_field = 1 AND t2.approved = 1';
        $data["code"] = DB::select( DB::raw($query));
        if($data["code"]){
            $datas = DB::select( DB::raw("select * from ps_cart_rule where id_customer=:customer and code = :request"), array('customer'=>0, 'request' =>$request->code));
            if($datas){
                $data["result"] = "202";
                //
                $matricules = $datas;
                foreach ($matricules as $value) {
                    $existMatricule = DB::select( DB::raw("select * from ps_customer where matricule = :requests"), array('requests'=>$value->matricule));
                    if(empty($existMatricule)){
                        $data["matricule"] = $value;
                        break; 
                    }
                }
                //$data["customer"] = DB::select( DB::raw("select * from ps_customer where matricule = :requests"), array('requests' =>$request->matricule));
            }
            else{
                $data["result"] = "203";
            }
        }
        else{
            $data["result"] = "404";
        }
        return $data;
    }

    public function generate_matricule(Request $request)
    {
        for ($x = 0; $x < $request->number; $x++) {
            do {
                $unique_code = $this->generateRandomNumber();
                $data = DB::select( DB::raw("select * from ps_cart_rule where matricule = :request"), array('request' => $unique_code));
                $arr = (array)$data;
            } while ($arr);
            $data = DB::table('ps_cart_rule')->insert(
                ['date_from' => Carbon::now()->format('Y-m-d'),
                 'date_to' => Carbon::now()->format('Y-m-d'),
                 'code' => $request->cse,
                 'date_add' => Carbon::now()->format('Y-m-d'),
                 'date_upd' => Carbon::now()->format('Y-m-d'),
                 'matricule' => $unique_code,
                 'id_group' => $request->cse_code,
                 'quantity' => 1,
                 'quantity_per_user' => 1,
                 'partial_use' => 1,
                ]
            );
            
        }
        $title = "Matricules générés avec succès sur Creuch";
        $customer_details = [
            'number' => $request->number,
            'cse' => $request->cse,
            'emailAdmin' => "team@inno-angels.com",
            'emailCSE' => "",
        ];
        $order_details = [];
        $sendmail = Mail::to($customer_details['emailAdmin'])->send(new SendMail($title, $customer_details, $order_details));
       // $sendmail = Mail::to($customer_details['emailCSE'])->send(new SendMail($title, $request->number, $order_details));
/*         if (empty($sendmail)) {
            return response()->json(['message' => 'Mail Sent Sucssfully'], 200);
        }else{
            return response()->json(['message' => 'Mail Sent fail'], 400);
        } */
        //return $datas["result"]= $request->number." matricules générés avec succès";
    }

    public function contact(Request $request)
    {
        $title = "Nouvelle demande d'inscription";
        $customer_details = [
            'name' => $request->firstname.' '.$request->lastname,
            'cse' => $request->cse,
            'emailAdmin' => ["team@inno-angels.com", "bonjour@creuch.fr"],
            'email' => $request->email,
        ];
        $order_details = "";
        $sendmail = Mail::to($customer_details['emailAdmin'])->send(new ContactMail($title, $customer_details, $order_details));
        if (empty($sendmail)) {
            return response()->json(['message' => 'Mail Sent Sucssfully'], 200);
        }else{
            return response()->json(['message' => 'Mail Sent fail'], 400);
        } 
    }

    public function generateRandomNumber($length = 8)
    {
        $random = "";
        srand((double) microtime() * 1000000);
        $data = "123456123456789071234567890890";
        for ($i = 0; $i < $length; $i++) {
                $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        return $random;
    }

    public function check_code_or_matricule(Request $request)
    {
        if (is_numeric($request->code)) {
            $data["type"] = "matricule";
            $data["matricule"] = DB::select( DB::raw("select * from ps_cart_rule where matricule = :request"), array('request' =>$request->code));
            if($data["matricule"]){
                $data["matricule"] = $data["matricule"][0];
                $data["customer"] = DB::select( DB::raw("select * from ps_customer where matricule = :requests"), array('requests' =>$request->code));
                if($data["customer"]){
                    $data["result"] = "201";
                }
                else{
                    $data["result"] = "202"; 
                }
            }
            else{
                $data["result"] = "404";
            }
            return $data;
        }
        else{
            $data["type"] = "code";
            $query = 'select t1.id_seller, t2.id_customer from ps_kb_mp_custom_field_seller_mapping t1 inner join ps_kb_mp_seller t2 on t1.id_seller = t2.id_seller where t1.value="'.$request->code.'" AND t1.id_field = 1 AND t2.approved = 1';
            $data["code"] = DB::select( DB::raw($query));
            if($data["code"]){
                $datas = DB::select( DB::raw("select * from ps_cart_rule where id_customer=:customer and code = :request"), array('customer'=>0, 'request' =>$request->code));
                if($datas){
                    $data["result"] = "202";
                    //$data["matricule"] = $datas[0];
                    $matricules = $datas;
                    foreach ($matricules as $value) {
                        $existMatricule = DB::select( DB::raw("select * from ps_customer where matricule = :requests"), array('requests'=>$value->matricule));
                        if(empty($existMatricule)){
                            $data["matricule"] = $value;
                            break; 
                        }
                    }
                }
                else{
                    $data["result"] = "203";
                }
            }
            else{
                $data["result"] = "404";
            }
            return $data;
        }
    }



    
    
    
    
    
    
}

