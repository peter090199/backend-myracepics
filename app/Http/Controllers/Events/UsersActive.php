<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UsersActive extends Controller
{
   /**
     * Get all active photographers from users and resources.
     */
    public function getPhotographers(): JsonResponse
    {
        // Define the first query
        $users = DB::table('users')
            ->select('fullname', 'role_code', 'status')
            ->where('role_code', 'DEF-PHOTOGRAPHER')
            ->where('recordstatus', 'active');

        // Union with the second query and get results
        $photographers = DB::table('resources')
            ->select('fullname', 'role_code', 'status')
            ->where('role_code', 'DEF-PHOTOGRAPHER')
            ->where('recordstatus', 'active')
            ->union($users)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $photographers,
            'count' => $photographers->count()
        ], 200);
    }
}
