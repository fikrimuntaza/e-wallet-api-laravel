<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Auth;

class TransactionController extends Controller
{
    protected $user;

    public function topup(Request $request) 
    {
        $data = $request->only('saldo');
        $validator = Validator::make($data, [
            'saldo' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }
        
        $cekrekening = User::where('no_rekening', Auth::user()->no_rekening)->first();

        if(!empty($cekrekening))
        {
        Transaction::create([
            'id_user' => $cekrekening->id,
            'jenis_transaksi' => 'topup',
            'saldo_masuk' => $request->saldo,
            'saldo_keluar' => 0
        ]);

        User::find($cekrekening->id)->update([
            'saldo' => $cekrekening->saldo + $request->saldo
        ]);
        
        $arr = array("code" => '00', "message" => "Top Up successfully");
        }
        else
        {
        $arr = array("code" => '04', "message" => "No Rekening Tidak Terdaftar");
        }

        return response()->json($arr);
    }

    public function withdraw(Request $request) 
    {

        $data = $request->only('saldo');
        $validator = Validator::make($data, [
            'saldo' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $cekrekening = User::where('no_rekening', Auth::user()->no_rekening)->first();

        if(!empty($cekrekening))
        {
            if($cekrekening->saldo >= $request->saldo)
            {
            Transaction::create([
                'id_user' => $cekrekening->id,
                'jenis_transaksi' => 'withdraw',
                'saldo_masuk' => 0,
                'saldo_keluar' => $request->saldo
            ]);

            User::find($cekrekening->id)->update([
                'saldo' => $cekrekening->saldo - $request->saldo
            ]);
            
            $arr = array("code" => '00', "message" => "Withdraw successfully");
            }
            else
            {
            $arr = array("code" => '04', "message" => "Saldo Tidak Mencukupi");
            }
            }
            else
            {
            $arr = array("code" => '04', "message" => "No Rekening Tidak Terdaftar");
            }

        return response()->json($arr);
    }

    public function transfer(Request $request) 
    {

        $data = $request->only('no_rekening_tujuan','saldo');
        $validator = Validator::make($data, [
            'no_rekening_tujuan' => 'required',
            'saldo' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        $cekrekeningTujuan = User::where('no_rekening', $request->no_rekening_tujuan)->first();

        if(!empty($cekrekeningTujuan))
        {
            if(Auth::user()->saldo >= $request->saldo)
            {

            Transaction::create([
                'id_user' => Auth::user()->id,
                'jenis_transaksi' => 'transfer',
                'saldo_masuk' => 0,
                'saldo_keluar' => $request->saldo
            ]);

            Transaction::create([
                'id_user' => $cekrekeningTujuan->id,
                'jenis_transaksi' => 'transfer',
                'saldo_masuk' => $request->saldo,
                'saldo_keluar' => 0
            ]);

            User::find(Auth::user()->id)->update([
                'saldo' => Auth::user()->saldo - $request->saldo
            ]);

            User::find($cekrekeningTujuan->id)->update([
                'saldo' => $cekrekeningTujuan->saldo + $request->saldo
            ]);
            
            $arr = array("code" => '00', "message" => "Transfer successfully");
            }
            else
            {
            $arr = array("code" => '04', "message" => "Saldo Tidak Mencukupi");
            }
            }
            else
            {
            $arr = array("code" => '04', "message" => "No Rekening Tidak Terdaftar");
            }

        return response()->json($arr);
    }

    public function mutasi() 
    {

        $transaction = Transaction::where('id_user', Auth::user()->id)->get();

        $arr = array("code" => '00', "message" => "Laporan Mutasi Tersedia", "data" => $transaction);


        return response()->json($arr);
    }
}