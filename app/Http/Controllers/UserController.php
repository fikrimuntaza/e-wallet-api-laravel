<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\Rules\Password;
use Auth;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        Log::create([
            'id_user' => Auth::user()->id,
            'aktivitas' => 'login'
        ]);

        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', Password::min(7)
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised()]
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'saldo' => 0,
            'no_rekening' => $request->get('no_rekening')
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => ['required', 'string', Password::min(7)
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised()]
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        if ((Hash::check(request('old_password'), Auth::user()->password)) == false) {
            $arr = array("code" => '04', "message" => "Periksa kata sandi lama Anda.", "data" => array());
        } else if ((Hash::check(request('new_password'), Auth::user()->password)) == true) {
            $arr = array("code" => '04', "message" => "Silakan masukkan kata sandi yang tidak mirip dengan kata sandi saat ini", "data" => array());
        } else {
            User::where('id', Auth::user()->id)->update([
                'password' => Hash::make(Hash::make($request->get('password')))
            ]);
            $arr = array("code" => '00', "message" => "Password updated successfully.", "data" => array());
        }

        return response()->json($arr);
    }

    public function getAuthenticatedUser()
    {
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }

        return response()->json(compact('user'));
    }
}