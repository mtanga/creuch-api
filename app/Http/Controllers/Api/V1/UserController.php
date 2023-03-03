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
                        //$data["result"] = "202";
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

    public function categegoriesuser(Request $request){

        $query = 'SELECT DISTINCT ps_category.*, ps_category_group.* FROM ps_category, ps_category_group WHERE ps_category_group.id_group="'.$request->group.'" AND ps_category.id_category = ps_category_group.id_category AND ps_category.active = 1';
        //$query = 'SELECT DISTINCT ps_category.*, ps_category_group.* FROM ps_category, ps_category_group WHERE ps_category_group.id_group='.$request->group;
        $getCategories = DB::select( DB::raw($query));

        //return $getCategories;

          $categories = [];

        foreach ($getCategories as $value) {


           $subcategories = [];
           $products= [];
           

            
            $catedgoriesDetails = DB::table('ps_category_lang')
            ->where('id_category', $value->id_category)
            ->get();
            if($catedgoriesDetails){
                $catedgoriesDetails = $catedgoriesDetails[0];
            }

            $query = 'SELECT id_category FROM ps_category WHERE id_parent='.$value->id_category;
            $subcategories = DB::select( DB::raw($query));
            //$subcategories= [];

   

            $queryp = '
            SELECT SQL_CALC_FOUND_ROWS p.id_product  AS id_product
            FROM  ps_product p 
            LEFT JOIN ps_product_lang pl ON (pl.id_product = p.id_product AND pl.id_lang = 1 AND pl.id_shop = 1) 
            LEFT JOIN ps_stock_available sav ON (sav.id_product = p.id_product AND sav.id_product_attribute = 0 AND sav.id_shop = 1  AND sav.id_shop_group = 0 ) 
            JOIN ps_product_shop sa ON (p.id_product = sa.id_product AND sa.id_shop = 1) 
            LEFT JOIN ps_category_lang cl ON (sa.id_category_default = cl.id_category AND cl.id_lang = 1 AND cl.id_shop = 1) 
            LEFT JOIN ps_category c ON (c.id_category = cl.id_category) 
            LEFT JOIN ps_shop shop ON (shop.id_shop = 1) 
            LEFT JOIN ps_image_shop image_shop ON (image_shop.id_product = p.id_product AND image_shop.cover = 1 AND image_shop.id_shop = 1) 
            LEFT JOIN ps_image i ON (i.id_image = image_shop.id_image) 
            LEFT JOIN ps_product_download pd ON (pd.id_product = p.id_product) 
            INNER JOIN ps_category_product cp ON (cp.id_product = p.id_product AND cp.id_category = "'.$value->id_category.'") 
            WHERE (1 AND state = 1)
            
            ORDER BY  id_product desc
            ';
            $products = DB::select( DB::raw($queryp));
            
     
            $item = [
                "id" => $value->id_category,
                "group" => $value->id_group,
                "trouves" => count($getCategories),
                "id_parent" => strval($value->id_parent),
                "level_depth" => $value->level_depth,
                "nb_products_recursive" => "882",
                "active" => $value->active,
                "id_shop_default" => $value->id_shop_default,
                "is_root_category" => $value->is_root_category,
                "position" => $value->position,
                "date_add" => $value->date_add,
                "date_upd" => $value->date_upd,
                "name" => $catedgoriesDetails->name,
                "link_rewrite" => $catedgoriesDetails->link_rewrite,
                "description" => $catedgoriesDetails->description,
                "meta_title" => $catedgoriesDetails->meta_title,
                "meta_description" => $catedgoriesDetails->meta_description,
                "meta_keywords" => $catedgoriesDetails->meta_keywords,
                "custom_field" => $value->custom_field,
                "date_end" => $value->date_end,
                "date_start" =>  $value->date_start,
                "description_sondage" =>  $value->description_sondage,
                "remise" =>  $value->remise,
                "franco" => $value->franco,
                "cashback" =>  $value->cashback,
                "image" =>  'https://prod.creuch.fr/c/'.$value->id_category.'-small_default/'.$catedgoriesDetails->link_rewrite.'.jpg',
                "date_livraison" =>  $value->date_livraison,
                "associations" =>  [
                    "categories" => $subcategories,
                    "products" => $products
                  ]
            ];
            array_push($categories, $item);
            }

       // }
        return $categories;
    }


    public function checkCategoryGroup($group, $category, $groups){
            foreach($groups as $arr_val){
              if($arr_val->id_group == $group && $arr_val->id_category == $category){
                return true;
              }
            }
            return false;
    }

    public function groupuser(Request $request){
        $data["user"] = DB::select( 
            DB::raw("select * from ps_customer_group where id_customer = :request and id_group = :requests"),
            array('request' =>$request->customer, 'requests' =>$request->group));
        if(!$data["user"]){
            $data["groupuser"] = DB::table('ps_customer_group')->insert(
                [
                 'id_customer' => $request->customer,
                 'id_group' => $request->group,
                ]
            );
            $data["result"] = "202";
        }
        else{
            $data["result"] = "201";
        }
        return $data;
    }

    public function pointretraits(Request $request){
       // return $request->group;
        $query = 'SELECT DISTINCT ps_carrier.*, ps_carrier_group.* FROM ps_carrier , ps_carrier_group WHERE ps_carrier_group.id_group = "'.$request->group.'" AND ps_carrier.id_carrier = ps_carrier_group.id_carrier';
        $carriers = DB::select( DB::raw($query));
        return $carriers;

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
            return response()->json(
                [
                'message' => 'Mail Sent Sucssfully',
                'status' => '200'
            ], 200);
        }else{
            return response()->json([
                'message' => 'Mail Sent fail',
                'status' => '400'    
            ], 400);
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

