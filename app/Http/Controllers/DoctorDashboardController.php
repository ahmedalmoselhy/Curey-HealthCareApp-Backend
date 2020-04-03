<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\User;
use App\Image;
use App\Doctor;
use App\Gender;
use App\Speciality;
use App\UserRole;
use App\DoctorRating;
use App\Appointment;
use App\DrPrescription;
use App\PrescriptionDe;
use App\Timetable;
use App\Day;
use App\City;
use App\Degree;
use App\Product;

class DoctorDashboardController extends Controller
{
    public function webDashboard(Request $request){
        //authentication
        $isFailed = false;
        $data = [];
        $errors =  [];

        // Global variables
        $past = [];

        $api_token = $request -> api_token;
        $user = null;
        $user = User::where(['api_token' => $api_token, 'role_id' => 3])->first();
        if($user == null){
            $isFailed = true;
            $errors += [
                'error' => 'authentication failed'
            ];
        }
        else{
            // get the basic logged in doctor information
            $doctor = Doctor::where('user_id', $user -> id)->first();
            if($doctor == null){
                $isFailed = true;
                $errors += [];
            }
            else{
                $speciality = Speciality::where('id', $doctor -> speciality_id)->first();
                $d_image_path = null;
                $d_image = Image::where('id', $user -> image_id)->first();
                if($d_image != null){
                    $d_image_path = $d_image -> path;
                }

                // get ratings
                $all_appointments = Appointment::where('doctor_id', $doctor -> id)->get();
                $appointments_count = Appointment::where('doctor_id', $doctor -> id)->count();
                $overall_rating = 0;
                if($all_appointments == null && $appointments_count == 0){
                    $overall_rating = 0;
                }
                else{
                    $review_count = 0;
                    $overall_rate = 0;
                    foreach($all_appointments as $appointment){
                        $appointment_id = $appointment -> id;
                        $rating = DoctorRating::where('appointment_id', $appointment_id)->first();
                        if($rating == null){
                            continue;
                        }
                        else{
                            $behavior = $rating -> behavior;
                            $price = $rating -> price;
                            $efficiency = $rating -> efficiency;
                            $appointment_rating = ($behavior + $price + $efficiency) / 3;
                            $overall_rate += $appointment_rating;
                            $review = $rating -> review;
                            //no of reviews
                            $review_count += 1;
                        }
                    }
                    if ($appointments_count != 0){
                        $overall_rating = $overall_rate / $appointments_count;
                    }
                }

                // doctor data response
                $dr = [
                    'name' => $user -> full_name,
                    'image' => $d_image_path,
                    'address' => $doctor -> address,
                    'speciality' => $speciality -> name,
                    'rating' => $overall_rating,
                    'no_reviews' => $review_count,
                ];

                // get all performed appointments
                $past_appointments = Appointment::where(['doctor_id' => $doctor -> id,])
                    ->where('appointment_time', '<', Carbon::now())->get();
                if($past_appointments -> isNotEmpty()){
                    foreach($past_appointments as $app){
                        // get patient data
                        $patient = User::where('id', $app -> user_id)->first();
                        $p_image_path = null;
                        $p_image = Image::where('id', $patient -> image_id)->first();
                        if($p_image != null){
                            $p_image_path = $p_image -> path;
                        }
                        $past_app = [
                            'patient' => $patient -> full_name,
                            'address' => $patient -> address,
                            'image' => $p_image_path,
                            'timestamp' => $app -> appointment_time,
                            'home_visit' => $app -> is_callup,
                            're_examination' => $app -> re_examination,
                        ];

                        $past[] = $past_app;
                    }
                }
                // else{
                //     $isFailed = true;
                //     $errors += [];
                // }
            }
        }

        if($isFailed == false){
            $data = [
                'doctor' => $dr,
                'performed' => $past,
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