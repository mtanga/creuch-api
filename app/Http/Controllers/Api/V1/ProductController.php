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


class ProductController extends BaseController
{
    
    
    
    

    public function categegoriesuser(Request $request){

        //Get primary category
        $query1 = 'SELECT * FROM ps_category WHERE active = 1 AND id_parent=2';
        $primary = DB::select( DB::raw($query1));
        //return $primary;


        //Get subcategories with group
        $query2 = 'SELECT DISTINCT ps_category.*, ps_category_group.* FROM ps_category, ps_category_group WHERE ps_category_group.id_group="'.$request->group.'" AND ps_category.id_category = ps_category_group.id_category AND ps_category.active = 1';
        $subctegories = DB::select( DB::raw($query2));
        //return $subctegories;

        //Get primary categories with group subcategories
        $primaryCategories = [];

        //Loo primary categories

        foreach ($primary as $value) {
            if($this->checkprimaryWithSub($value, $subctegories)==true){
                array_push($primaryCategories, $value);
            }

        }
        
        $outputCatwgories = array_merge( $primaryCategories , $subctegories );
        //return count($outputCatwgories);
        //return $outputCatwgories;

        $categories = [];
        foreach ($outputCatwgories as $value) {
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
                "group" => $value->id_group ?? "",
                "trouves" => count($outputCatwgories),
                "id_parent" => strval($value->id_parent),
                "level_depth" => $value->level_depth,
                "nb_products_recursive" => count($products),
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

            return $categories;
    }


    public function checkprimaryWithSub($item, $sub){
        foreach ($sub as $x) {
            if($x->id_parent == $item->id_category){
                return true;
            }
            
        }
        return false;
    }


    
    
}

