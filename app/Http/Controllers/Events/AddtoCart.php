<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddtoCart extends Controller
{
    public function addtoCart(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'img_id'    => 'required',
            'img_name'  => 'required',
            'img_price' => 'required|numeric',
            'img_qty'   => 'required|integer',
            'fullname'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Save or update the cart record
        $cart = ImageCart::updateOrCreate(
            [
                'img_id'  => $request->img_id,
                'evnt_id' => $request->evnt_id, // Match unique product + event
                'code'    => $request->code
            ],
            [
                'img_name'     => $request->img_name,
                'img_price'    => $request->img_price,
                'img_qty'      => $request->img_qty,
                'platform_fee' => $request->platform_fee ?? 0,
                'service_fee'  => $request->service_fee ?? 0,
                'status'       => $request->status ?? 'active',
                'role_code'    => $request->role_code,
                'fullname'     => $request->fullname,
                'evnt_name'    => $request->evnt_name,
                'recordstatus' => $request->recordstatus ?? 1,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Item added to basket',
            'data' => $cart
        ], 201);
    }
}
