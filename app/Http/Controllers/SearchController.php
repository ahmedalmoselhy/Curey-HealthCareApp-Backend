<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\City;
use App\Doctor;
use App\Image;
use App\Speciality;
use App\DoctorRating;
use App\Appointment;
use App\Keyword;
use App\Product;
use App\ProductKeyword;
use App\Pharmacy;
use App\ProductPharmacy;
use App\PharmacyRating;
use App\Order;
use App\Favourite;

class SearchController extends Controller
{

    public function mobileSearchDoctors(Request $request){
        $name = $request -> name;
        $isFailed = false;
        $data = [];
        $errors =  [];

        $api_token = $request -> api_token;
        // $user = null;
        $user = User::where('api_token', $api_token)->first();
        $doctors_response = [];
        $specs = [];
        if ($user == null){
            $isFailed = true;
            $errors+= [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $spec_filter = $request -> speciality_id;
            $city_filter = $request -> city_id;
            $skip = $request -> skip;
            $limit = $request -> limit;
            $doctors_user = null;
            if($city_filter != null){
                $doctors_user = User::where('full_name', 'LIKE', '%'. $name .'%')
                    ->where(['role_id' => 3, 'city_id' => $city_filter])
                    ->orderBy('id', 'asc')
                    ->skip($skip)
                    ->take($limit)
                    ->get();
            }
            else{
                $doctors_user = User::where('full_name', 'LIKE', '%'. $name .'%')
                    ->where('role_id', '3')
                    ->orderBy('id', 'asc')
                    ->skip($skip)
                    ->take($limit)
                    ->get();
            }
            if($doctors_user -> isEmpty()){
                $isFailed = true;
                $errors += [
                    'error' => 'no results'
                ];
            }
            else{
                foreach ($doctors_user as $doctor_user){
                    $id = $doctor_user -> id;
                    $doc = null;
                    if($spec_filter != null){
                        $doc = Doctor::where('user_id', $id)
                            ->where('speciality_id', $spec_filter)
                            ->first();
                    }
                    else{
                        $doc = Doctor::where('user_id', $id)->first();
                    }
                    if($doc != null){
                        $spec_id = $doc -> speciality_id;
                        $speciality = Speciality::where('id', $spec_id)->first();
                        if ($speciality == NULL){
                            $s_name = NULL;
                        }
                        else{
                            $s_name = $speciality -> name;
                        }
                        // Get the doctor's photo
                        $image_id = $doctor_user -> image_id;
                        $image = Image::where('id', $image_id)->first();
                        if($image != null){
                            $image_path = Storage::url($image -> path . '.' .$image -> extension);
                            $image_url = asset($image_path);
                        }
                        else{
                            $image_url = asset(Storage::url('default/doctor.png'));
                        }

                        // show overall rating
                        $doc_id = $doc -> id;
                        $appointments = Appointment::where('doctor_id', $doc_id)->get();
                        $appointments_count = $appointments->count();
                        $ratings = 0;
                        $overall_rating = 0;
                        if(!($appointments->isEmpty())){
                            $overall_rate = 0;
                            foreach($appointments as $appointment){
                                $appointment_id = $appointment -> id;
                                $rating = DoctorRating::where('appointment_id', $appointment_id)->first();
                                $appointment_rating = 0;

                                if($rating != null){
                                    $behavior = $rating -> behavior;
                                    $price = $rating -> price;
                                    $efficiency = $rating -> efficiency;
                                    $appointment_rating = ($behavior + $price + $efficiency) / 3;
                                    $overall_rate += $appointment_rating;
                                }
                            }
                            $overall_rating = $overall_rate / $appointments_count;
                            $ratings = $overall_rating;
                        }
                        else{
                            $ratings = 0;
                        }

                        $doctor = [
                            'id' => $doc -> id,
                            'full_name' => $doctor_user -> full_name,
                            'speciality' => $s_name,
                            'image' => $image_url,
                            'fees' => $doc -> fees,
                            'offers_callup' => $doc -> offers_callup,
                            'overall_rating' => $ratings,
                            'city_id' => $doctor_user -> city_id,
                        ];
                        $doctors_response[] = $doctor;
                    }

                }
            }
            $specialities = Speciality::all();
            if($specialities -> isEmpty()){
                $specs = [];
            }
            else{
                foreach($specialities as $spec){
                    $specs[] = [
                        'id' => $spec -> id,
                        'name' => $spec -> name,
                    ];
                }
            }
            $cities_data = City::all();
            $cities = [];
            foreach($cities_data as $city){
                $cities[] = [
                    'id' => $city -> id,
                    'name' => $city -> name,
                ];
            }
            $data = [
                'doctors' => $doctors_response,
                'specialities' => $specs,
                'cities' => $cities,
            ];
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }

    public function mobileSearchProducts(Request $request){
        $name = $request -> name;
        $isFailed = false;
        $data = [];
        $errors =  [];

        $api_token = $request -> api_token;
        $user = null;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors+= [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $skip = $request -> skip;
            $limit = $request -> limit;
            $keywords_filter = $request -> keywords;
            if($keywords_filter != []){
                $products_ids = ProductKeyword::select('product_id')->whereIn('keyword_id', $keywords_filter)->get();
                $ids = [];
                foreach($products_ids as $it){
                    if(in_array($it -> product_id, $ids)){
                        continue;
                    }
                    else{
                        $ids[] = $it -> product_id;
                    }
                }
            }
            $products = Product::where('name', 'LIKE', '%'. $name .'%')->skip($skip)->take($limit)->get();
            if($products->isEmpty()){
                $isFailed = true;
                $errors[] = [
                    'error' => 'no results'
                ];
            }
            else{
                $products_response = [];
                if($keywords_filter != []){
                    foreach($products as $pro){
                        if(in_array($pro -> id, $ids)){
                            $image_id = $pro -> image_id;
                            $image = Image::where('id', $image_id)->first();

                            if($image != null){
                                $image_path = Storage::url($image -> path . '.' .$image -> extension);
                                $image_url = asset($image_path);
                            }
                            else{
                                $image_url = asset(Storage::url('default/product.png'));
                            }

                            $favourite = Favourite::where('user_id', $user -> id)->where('product_id', $pro -> id)->first();
                            $isFav = false;
                            if($favourite != null){
                                $isFav = true;
                            }
                            $keywords = ProductKeyword::where('product_id' , $pro -> id)->get();
                            $keywords_ids = [];
                            if($keywords -> isNotEmpty()){
                                foreach($keywords as $keyword){
                                    $keyword_id = $keyword -> keyword_id;
                                    $keywords_ids[] = $keyword_id;
                                }
                            }
                            $final_product = [
                                'id' => $pro -> id,
                                'name' => $pro -> name,
                                'image' => $image_url,
                                'price' => $pro -> price,
                                'is_favourite' => $isFav,
                                'keywords' => $keywords_ids,
                            ];

                            $products_response[] = $final_product;
                        }
                    }
                }
                else{
                    foreach($products as $pro){
                        $image_id = $pro -> image_id;
                        $image = Image::where('id', $image_id)->first();

                        if($image != null){
                            $image_path = Storage::url($image -> path . '.' .$image -> extension);
                            $image_url = asset($image_path);
                        }
                        else{
                            $image_url = asset(Storage::url('default/product.png'));
                        }

                        $favourite = Favourite::where('user_id', $user -> id)->where('product_id', $pro -> id)->first();
                        $isFav = false;
                        if($favourite != null){
                            $isFav = true;
                        }
                        $keywords = ProductKeyword::where('product_id' , $pro -> id)->get();
                        $keywords_ids = [];
                        if($keywords -> isNotEmpty()){
                            foreach($keywords as $keyword){
                                $keyword_id = $keyword -> keyword_id;
                                $keywords_ids[] = $keyword_id;
                            }
                        }
                        $final_product = [
                            'id' => $pro -> id,
                            'name' => $pro -> name,
                            'image' => $image_url,
                            'price' => $pro -> price,
                            'is_favourite' => $isFav,
                            'keywords' => $keywords_ids,
                        ];

                        $products_response[] = $final_product;
                    }
                }

            }
        }
        // get keywords for filters
        $keywords = Keyword::all();

        foreach($keywords as $key){
            $keywords_response[] = [
                'id' => $key -> id,
                'name' => $key -> name,
            ];
        }
        if($isFailed == false){
            $data = [
                'products' => $products_response,
                'keywords' => $keywords_response,
            ];
        }
        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }

    /* Web Tailored Functions */
    /* ***************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    ******************************************************************************************************
    */

    public function webSearchDoctors(Request $request){
        $name = $request -> name;
        $isFailed = false;
        $data = [];
        $errors =  [];

        $api_token = $request -> api_token;
        // $user = null;
        $user = User::where('api_token', $api_token)->first();
        $doctors_response = [];
        $specs = [];
        if ($user == null){
            $isFailed = true;
            $errors+= [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $spec_filter = $request -> speciality_id;
            $city_filter = $request -> city_id;
            $skip = $request -> skip;
            $limit = $request -> limit;
            $doctors_user = null;
            if($city_filter != null){
                $doctors_user = User::where('full_name', 'LIKE', '%'. $name .'%')
                    ->where(['role_id' => 3, 'city_id' => $city_filter])
                    ->orderBy('id', 'asc')
                    ->skip($skip)
                    ->take($limit)
                    ->get();
            }
            else{
                $doctors_user = User::where('full_name', 'LIKE', '%'. $name .'%')
                    ->where('role_id', '3')
                    ->orderBy('id', 'asc')
                    ->skip($skip)
                    ->take($limit)
                    ->get();
            }
            if($doctors_user -> isEmpty()){
                $isFailed = true;
                $errors += [
                    'error' => 'no results'
                ];
            }
            else{
                foreach ($doctors_user as $doctor_user){
                    $id = $doctor_user -> id;
                    $doc = null;
                    if($spec_filter != null){
                        $doc = Doctor::where('user_id', $id)
                            ->where('speciality_id', $spec_filter)
                            ->first();
                    }
                    else{
                        $doc = Doctor::where('user_id', $id)->first();
                    }
                    if($doc != null){
                        $spec_id = $doc -> speciality_id;
                        $speciality = Speciality::where('id', $spec_id)->first();
                        if ($speciality == NULL){
                            $s_name = NULL;
                        }
                        else{
                            $s_name = $speciality -> name;
                        }
                        // Get the doctor's photo
                        $image_id = $doctor_user -> image_id;
                        $image = Image::where('id', $image_id)->first();
                        if($image != null){
                            $image_path = Storage::url($image -> path . '.' .$image -> extension);
                            $image_url = asset($image_path);
                        }
                        else{
                            $image_url = asset(Storage::url('default/doctor.png'));
                        }

                        // show overall rating
                        $doc_id = $doc -> id;
                        $appointments = Appointment::where('doctor_id', $doc_id)->get();
                        $appointments_count = $appointments->count();
                        $ratings = 0;
                        $overall_rating = 0;
                        if(!($appointments->isEmpty())){
                            $overall_rate = 0;
                            foreach($appointments as $appointment){
                                $appointment_id = $appointment -> id;
                                $rating = DoctorRating::where('appointment_id', $appointment_id)->first();
                                $appointment_rating = 0;

                                if($rating != null){
                                    $behavior = $rating -> behavior;
                                    $price = $rating -> price;
                                    $efficiency = $rating -> efficiency;
                                    $appointment_rating = ($behavior + $price + $efficiency) / 3;
                                    $overall_rate += $appointment_rating;
                                }
                            }
                            $overall_rating = $overall_rate / $appointments_count;
                            $ratings = $overall_rating;
                        }
                        else{
                            $ratings = 0;
                        }

                        $doctor = [
                            'id' => $doc -> id,
                            'full_name' => $doctor_user -> full_name,
                            'speciality' => $s_name,
                            'image' => $image_url,
                            'fees' => $doc -> fees,
                            'offers_callup' => $doc -> offers_callup,
                            'overall_rating' => $ratings,
                            'city_id' => $doctor_user -> city_id,
                        ];
                        $doctors_response[] = $doctor;
                    }

                }
            }
            $specialities = Speciality::all();
            if($specialities -> isEmpty()){
                $specs = [];
            }
            else{
                foreach($specialities as $spec){
                    $specs[] = [
                        'id' => $spec -> id,
                        'name' => $spec -> name,
                    ];
                }
            }
            $cities_data = City::all();
            $cities = [];
            foreach($cities_data as $city){
                $cities[] = [
                    'id' => $city -> id,
                    'name' => $city -> name,
                ];
            }
            $data = [
                'doctors' => $doctors_response,
                'specialities' => $specs,
                'cities' => $cities,
            ];
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }

    public function webSearchProducts(Request $request){
        $name = $request -> name;
        $isFailed = false;
        $data = [];
        $errors =  [];

        $api_token = $request -> api_token;
        $user = null;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors+= [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $skip = $request -> skip;
            $limit = $request -> limit;
            $keywords_filter = $request -> keywords;
            if($keywords_filter != []){
                $products_ids = ProductKeyword::select('product_id')->whereIn('keyword_id', $keywords_filter)->get();
                $ids = [];
                foreach($products_ids as $it){
                    if(in_array($it -> product_id, $ids)){
                        continue;
                    }
                    else{
                        $ids[] = $it -> product_id;
                    }
                }
            }
            $products = Product::where('name', 'LIKE', '%'. $name .'%')->skip($skip)->take($limit)->get();
            if($products->isEmpty()){
                $isFailed = true;
                $errors[] = [
                    'error' => 'no results'
                ];
            }
            else{
                $products_response = [];
                if($keywords_filter != []){
                    foreach($products as $pro){
                        if(in_array($pro -> id, $ids)){
                            $image_id = $pro -> image_id;
                            $image = Image::where('id', $image_id)->first();

                            if($image != null){
                                $image_path = Storage::url($image -> path . '.' .$image -> extension);
                                $image_url = asset($image_path);
                            }
                            else{
                                $image_url = asset(Storage::url('default/product.png'));
                            }

                            $favourite = Favourite::where('user_id', $user -> id)->where('product_id', $pro -> id)->first();
                            $isFav = false;
                            if($favourite != null){
                                $isFav = true;
                            }
                            $keywords = ProductKeyword::where('product_id' , $pro -> id)->get();
                            $keywords_ids = [];
                            if($keywords -> isNotEmpty()){
                                foreach($keywords as $keyword){
                                    $keyword_id = $keyword -> keyword_id;
                                    $keywords_ids[] = $keyword_id;
                                }
                            }
                            $final_product = [
                                'id' => $pro -> id,
                                'name' => $pro -> name,
                                'image' => $image_url,
                                'price' => $pro -> price,
                                'is_favourite' => $isFav,
                                'description' => $pro -> description,
                                'keywords' => $keywords_ids,
                            ];

                            $products_response[] = $final_product;
                        }
                    }
                }
                else{
                    foreach($products as $pro){
                        $image_id = $pro -> image_id;
                        $image = Image::where('id', $image_id)->first();

                        if($image != null){
                            $image_path = Storage::url($image -> path . '.' .$image -> extension);
                            $image_url = asset($image_path);
                        }
                        else{
                            $image_url = asset(Storage::url('default/product.png'));
                        }

                        $favourite = Favourite::where('user_id', $user -> id)->where('product_id', $pro -> id)->first();
                        $isFav = false;
                        if($favourite != null){
                            $isFav = true;
                        }
                        $keywords = ProductKeyword::where('product_id' , $pro -> id)->get();
                        $keywords_ids = [];
                        if($keywords -> isNotEmpty()){
                            foreach($keywords as $keyword){
                                $keyword_id = $keyword -> keyword_id;
                                $keywords_ids[] = $keyword_id;
                            }
                        }
                        $final_product = [
                            'id' => $pro -> id,
                            'name' => $pro -> name,
                            'image' => $image_url,
                            'price' => $pro -> price,
                            'is_favourite' => $isFav,
                            'description' => $pro -> description,
                            'keywords' => $keywords_ids,
                        ];

                        $products_response[] = $final_product;
                    }
                }
            }
        }
        // get keywords for filters
        $keywords = Keyword::all();

        foreach($keywords as $key){
            $keywords_response[] = [
                'id' => $key -> id,
                'name' => $key -> name,
            ];
        }
        if($isFailed == false){
            $data = [
                'products' => $products_response,
                'keywords' => $keywords_response,
            ];
        }
        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
}
