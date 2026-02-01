<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event\ImageCart;
use Illuminate\Support\Facades\Auth;

class AddtoCart extends Controller
{

   public function addToCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        // 1. Validation
        $validated = $request->validate([
            'img_id'        => 'required',
            'evnt_id'       => 'required',
            'img_name'      => 'required|string',
            'img_price'     => 'required|numeric',
            'watermark_url' => 'required|string',
            'evnt_name'     => 'required|string',
            'platform_fee'  => 'nullable|numeric',
            'service_fee'   => 'nullable|numeric',
        ]);

        // 2. Check if already in cart for this specific user
        $exists = ImageCart::where('code', $user->code)
                            ->where('img_id', $request->img_id)
                            ->where('evnt_id', $request->evnt_id)
                            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false, 
                'message' => 'This photo is already in your basket'
            ], 200); // 200 because it's a valid business logic stop, not a server error
        }

        // 3. Create record
        $cart = new ImageCart();
        $cart->img_id        = $request->img_id;
        $cart->img_name      = $request->img_name;
        $cart->img_price     = $request->img_price;
        $cart->img_qty       = $request->img_qty ?? 1;
        $cart->watermark_url = $request->watermark_url;
        $cart->platform_fee  = $request->platform_fee ?? 0;
        $cart->service_fee   = $request->service_fee ?? 0;
        $cart->status        = 'Pending'; 
        
        // Use Auth data for user details to prevent spoofing
        $cart->code          = $user->code;
        $cart->role_code     = $user->role_code;
        $cart->fullname      = $user->fullname;
        
        $cart->evnt_id       = $request->evnt_id;
        $cart->evnt_name     = $request->evnt_name;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Added to basket',
            'data'    => $cart,
            'cart_count' => ImageCart::where('code', $user->code)->count() // Helpful for UI
        ], 200);
    }

    public function getCartList()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Retrieve all items in the user's basket
        $cartItems = ImageCart::where('code', $user->code)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json([
            'success' => true,
            'count'   => $cartItems->count(),
            'data'    => $cartItems,
            // Calculate total price directly on the backend for accuracy
            'total_amount' => $cartItems->sum('img_price') 
        ], 200);
    }

    public function removeFromCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $deleted = ImageCart::where('code', $user->code)
                            ->where('img_id', $request->img_id)
                            ->where('evnt_id', $request->evnt_id)
                            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true, 
                'message' => 'Removed from basket',
                'cart_count' => ImageCart::where('code', $user->code)->count()
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    public function addtoCartxx(Request $request)
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
