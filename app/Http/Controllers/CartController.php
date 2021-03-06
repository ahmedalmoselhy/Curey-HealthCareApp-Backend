<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\ProductPharmacy;
use App\Cart;
use App\Product;
use App\Pharmacy;
use App\Image;

class CartController extends Controller
{
    // Mobile Functions
    public function mobileCreate(Request $request){
        $isFailed = false;
        $data = [];
        $errors = [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product = $request -> product;
            if($product == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not register an empty product ya Hacker ya ZAKI'
                ];
            }
            else{
                $item_id = $product['id'];
                $existing = Cart::where(['user_id' => $user -> id, 'product_id' => $item_id])->get();
                if($existing -> isEmpty()){
                    $cart_item = new Cart;
                    $cart_item -> user_id = $user -> id;
                    $cart_item -> product_id = $product['id'];
                    $cart_item -> amount = $product['amount'];
                    if($cart_item -> save()){
                        $data += [
                            'success' => 'item added to cart'
                        ];
                    }
                }
                else{
                    $isFailed = true;
                    $errors += [
                        'error' => 'this item already exists in your cart ya Customer'
                    ];
                }
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function mobileRead(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];

        $products = [];

        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $cart_items = Cart::where('user_id', $user -> id)->get();
            if($cart_items -> isEmpty()){
                $isFailed = true;
                $errors += [
                    'error' => 'you do not have any items ya Fa2er'
                ];
            }
            else{
                foreach($cart_items as $item){
                    $product_pharmacy_id = $item -> product_id;
                    $amount = $item -> amount;
                    $product = null;
                    $product_pharmacy = ProductPharmacy::where('id', $product_pharmacy_id)->first();
                    if($product_pharmacy == null){
                        $isFailed = true;
                        $errors += [
                            'error' => 'can not retrieve item data'
                        ];
                    }
                    else{
                        $product_id = $product_pharmacy -> product_id;
                        $pharmacy_id = $product_pharmacy -> pharmacy_id;
                        $product = Product::where('id', $product_id)->first();
                        if($product != null){
                            $pharmacy = Pharmacy::where('id', $pharmacy_id)->first();
                            if($pharmacy != null){
                                $pharmacy_user = User::where('id', $pharmacy -> user_id)->first();
                                if($pharmacy_user != null){
                                    $p_image = Image::where('id', $pharmacy_user -> image_id)->first();
                                    $p_image_path = null;
                                    if($p_image != null){
                                        $p_image_path = Storage::url($p_image -> path . '.' .$p_image -> extension);
                                        $p_image_url = asset($p_image_path);
                                    }
                                    else{
                                        $p_image_url = asset(Storage::url('default/pharmacy.png'));
                                    }

                                    $pharmacy_obj = [
                                        'name' => $pharmacy_user -> full_name,
                                        'address' => $pharmacy -> address,
                                        'image' => $p_image_url,
                                        'product_pharmacy_id' => $item -> product_id,
                                        'count' => $product_pharmacy -> count,
                                    ];
                                    $image = Image::where('id', $product -> image_id)->first();
                                    $image_path = null;
                                    if($image != null){
                                        $image_path = Storage::url($image -> path . '.' .$image -> extension);
                                        $image_url = asset($image_path);
                                    }
                                    else{
                                        $image_url = asset(Storage::url('default/product.png'));
                                    }
                                    $product_obj = [
                                        'id' => $product -> id,
                                        'name' => $product -> name,
                                        'price' => $product -> price,
                                        'amount' => $amount,
                                        'image' => $image_url,
                                        'pharmacy' => $pharmacy_obj
                                    ];
                                }
                                $products[] = $product_obj;
                            }
                        }
                    }
                }
            }
        }
        if($isFailed == false){
            $data = $products;
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function mobileUpdate(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product = $request -> product;
            if($product == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not update an empty product ya Hacker ya ZAKI'
                ];
            }
            else{
                Cart::where(['user_id' => $user -> id, 'product_id' => $product['id']])->update(['amount' => $product['amount']]);
                $data += [
                    'success' => 'updated successfully'
                ];
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function mobileDelete(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product_id = $request -> product_id;
            if($product_id == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not delete a non existing cart item ya Hacker ya ZAKI'
                ];
            }
            else{
                Cart::where(['user_id' => $user -> id, 'product_id' => $product_id])->delete();
                $data += [
                    'success' => 'deleted successfully'
                ];
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }

    // web functions
    public function webCreate(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product = $request -> product;
            if($product == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not register an empty product ya Hacker ya ZAYAN'
                ];
            }
            else{
                $item_id = $product['id'];
                $existing = Cart::where(['user_id' => $user -> id, 'product_id' => $item_id])->get();
                if($existing -> isEmpty()){
                    $product = ProductPharmacy::where('id', $item_id)->first();
                    if($product -> count > $product['amount']){
                        $cart_item = new Cart;
                        $cart_item -> user_id = $user -> id;
                        $cart_item -> product_id = $product['id'];
                        $cart_item -> amount = $product['amount'];
                        if($cart_item -> save()){
                            $data += [
                                'success' => 'item added to cart'
                            ];
                        }
                    }
                    else{
                        $isFailed = true;
                        $errors += [
                            'error' => 'not enough stock at pharmacy'
                        ];
                    }
                }
                else{
                    $isFailed = true;
                    $errors += [
                        'error' => 'this item already exists in your cart'
                    ];
                }
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function webRead(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];

        $products = [];

        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $cart_items = Cart::where('user_id', $user -> id)->get();
            if($cart_items -> isEmpty()){
                $isFailed = true;
                $errors += [
                    'error' => 'you do not have any items ya Fa2er'
                ];
            }
            else{
                foreach($cart_items as $item){
                    $product_pharmacy_id = $item -> product_id;
                    $amount = $item -> amount;
                    $product = null;
                    $product_pharmacy = ProductPharmacy::where('id', $product_pharmacy_id)->first();
                    if($product_pharmacy == null){
                        $isFailed = true;
                        $errors += [
                            'error' => 'can not retrieve item data'
                        ];
                    }
                    else{
                        $product_id = $product_pharmacy -> product_id;
                        $pharmacy_id = $product_pharmacy -> pharmacy_id;
                        $product = Product::where('id', $product_id)->first();
                        if($product != null){
                            $pharmacy = Pharmacy::where('id', $pharmacy_id)->first();
                            if($pharmacy != null){
                                $pharmacy_user = User::where('id', $pharmacy -> user_id)->first();
                                if($pharmacy_user != null){
                                    $p_image = Image::where('id', $pharmacy_user -> image_id)->first();
                                    $p_image_path = null;
                                    if($p_image != null){
                                        $p_image_path = Storage::url($p_image -> path . '.' .$p_image -> extension);
                                        $p_image_url = asset($p_image_path);
                                    }
                                    else{
                                        $p_image_url = asset(Storage::url('default/pharmacy.png'));
                                    }
                                    $pharmacy_obj = [
                                        'name' => $pharmacy_user -> full_name,
                                        'address' => $pharmacy -> address,
                                        'image' => $p_image_url,
                                        'product_pharmacy_id' => $item -> product_id,
                                        'count' => $product_pharmacy -> count,
                                    ];
                                    $image = Image::where('id', $product -> image_id)->first();
                                    $image_path = null;
                                    if($image != null){
                                        $image_path = Storage::url($image -> path . '.' .$image -> extension);
                                        $image_url = asset($image_path);
                                    }
                                    else{
                                        $image_url = asset(Storage::url('default/product.png'));
                                    }
                                    $product_obj = [
                                        'id' => $product -> id,
                                        'name' => $product -> name,
                                        'price' => $product -> price,
                                        'amount' => $amount,
                                        'image' => $image_url,
                                        'pharmacy' => $pharmacy_obj
                                    ];
                                }
                                $products[] = $product_obj;
                            }
                        }
                    }
                }
            }
        }
        if($isFailed == false){
            $data = $products;
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function webUpdate(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product = $request -> product;
            if($product == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not update an empty product'
                ];
            }
            else{
                Cart::where(['user_id' => $user -> id, 'product_id' => $product['id']])->update(['amount' => $product['amount']]);
                $data += [
                    'success' => 'updated successfully'
                ];
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
    public function webDelete(Request $request){
        $isFailed = false;
        $data = [];
        $errors =  [];
        $api_token = $request -> api_token;
        $user = User::where('api_token', $api_token)->first();

        if ($user == null){
            $isFailed = true;
            $errors += [
                'auth' => 'authentication failed'
            ];
        }
        else{
            $product_id = $request -> product_id;
            if($product_id == null){
                $isFailed = true;
                $errors += [
                    'error' => 'can not delete a non existing cart item'
                ];
            }
            else{
                Cart::where(['user_id' => $user -> id, 'product_id' => $product_id])->delete();
                $data += [
                    'success' => 'deleted successfully'
                ];
            }
        }

        $response = [
            'isFailed' => $isFailed,
            'data' => $data,
            'errors' => $errors
        ];

        return response()->json($response);
    }
}
