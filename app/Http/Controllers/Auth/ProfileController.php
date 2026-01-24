<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Resource;
use App\Models\Usercapabilitie;
use App\Models\Usereducation;
use App\Models\Userprofile;
use App\Models\Userseminar;
use App\Models\Usertraining;
use App\Models\Userskill;
use App\Models\Useremploymentrecord;
use App\Models\Usercertificate;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;




class ProfileController extends Controller
{
    public function uploadCoverPhoto(Request $request)
    {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        $user = Auth::user();
        $userCode = $user->code;
        $coverPhotoPath = null;

        try {
            DB::beginTransaction();

            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'coverphoto' => 'required|file|image|mimes:jpeg,jpg,png,gif|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            // 🔹 Delete the old cover photo folder if exists
            if ($user->coverphoto) {
                // Convert full URL to relative storage path
                $relativePath = str_replace(
                    'https://exploredition.com/storage/app/public/',
                    '',
                    $user->coverphoto
                );

                // Get the folder containing the file
                $folderPath = dirname($relativePath);

                // Delete entire folder from 'public' disk
                if (Storage::disk('public')->exists($folderPath)) {
                    Storage::disk('public')->deleteDirectory($folderPath);
                }
            }

            // 🔹 Handle new file upload
            if ($request->hasFile('coverphoto')) {
                $file = $request->file('coverphoto');
                $uuid = Str::uuid();
                $folderPath = "uploads/{$userCode}/coverphoto/{$uuid}";
                $fileName = time() . '.' . $file->getClientOriginalExtension();

                // Store file in the "public" disk (storage/app/public)
                $file->storeAs($folderPath, $fileName, 'public');

                // Build accessible URL
                $coverPhotoPath = "https://exploredition.com/storage/app/public/{$folderPath}/{$fileName}";
            }

            // 🔹 Update in database
            DB::table('users')
                ->where('code', $userCode)
                ->update([
                    'coverphoto' => $coverPhotoPath,
                    'updated_at' => now(),
                ]);

            DB::table('resources')
                ->where('code', $userCode)
                ->update([
                    'coverphoto' => $coverPhotoPath,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cover photo uploaded successfully.',
                'coverphoto' => $coverPhotoPath
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function index()
    {
         if (Auth::check()) {

            $userprofile = Userprofile::where('code',Auth::user()->code)->get();


            $result =[];
            for($up = 0; $up<count($userprofile); $up++){
                $resources = Resource::select('fname','lname','email','profession')->where('code',Auth::user()->code)->get();
                
                 $result = [
                    "code" => Auth::user()->code,
                    "email" => $resources[0]->email,
                    "fname" => $resources[0]->fname,
                    "lname" => $resources[0]->lname,
                    "photo_pic" =>  $userprofile[$up]->photo_pic?? 'https://exploredition.com/storage/app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'  ,
                    "contact_no" => $userprofile[$up]->contact_no,
                    "contact_visibility" => $userprofile[$up]->contact_visibility,
                    "email_visibility" => $userprofile[$up]->email_visibility,
                    "summary" => $userprofile[$up]->summary,
                    "date_birth" => $userprofile[$up]->date_birth,
                    "home_country" => $userprofile[$up]->home_country,
                    "current_location" => $userprofile[$up]->current_location,
                    "home_state" => $userprofile[$up]->home_state,
                    "current_state" => $userprofile[$up]->current_state,
                     "profession" => $resources[0]->profession,
                    "lines" => [
                        "education" =>  Usereducation::select('highest_education','school_name','start_month','start_year','end_month','end_year','status')->where('code',Auth::user()->code)->get(),
                        "language"=>  Usercapabilitie::select('language')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "training" => Usertraining::select('training_title','training_provider','date_completed')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "seminar" =>  Userseminar::select('seminar_title','seminar_provider','date_completed')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "skills" => Userskill::select('skills')->where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "employment" => Useremploymentrecord::select('company_name','position','job_description','date_completed')->where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "certificate" =>  Usercertificate::where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get()
                    ]
                ];
            }

            return response()->json(['success' => true,'message' => $result]);
        } 
        else {
            return response()->json(['success'=>false,'message' => 'User is not authenticated']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
            // $data = $request->all();
        //  return response()->json(['testn' => $data]);
         // Check if the user is authenticated
         if (Auth::check()) {
             try {
                 DB::beginTransaction();
                 $data = $request->all();
                 // Validate the request data for the user profile
                 $validator = Validator::make($data, [
                     //'photo_pic' => 'nullable|file|image|max:2048', // Validate file upload
                     'contact_no' => 'nullable|string|max:255',
                     'contact_visibility' => 'nullable|integer',
                     'email_visibility' => 'nullable|integer',
                     'summary' => 'nullable|string',
                     'date_birth' => 'nullable|date',
                     'home_country' => 'nullable|string|max:255',
                     'current_location' => 'nullable|string|max:255',
                 ]);
     
                 // Check for validation errors
                 if ($validator->fails()) {
                     return response()->json([
                         'success' => false,
                         'message' => $validator->errors()->all(),
                     ]);
                 }
     
                 $exist = UserProfile::where('code', Auth::user()->code)->exists();
     
                // Get the new transaction number
                $transNo = UserProfile::max('transNo');
                $newtrans = empty($transNo) ? 1 : $transNo + 1;
                $transNoToUse = $exist ? UserProfile::where('code', Auth::user()->code)->value('transNo') : $newtrans;


                if ($exist) {
                    UserProfile::where('code',Auth::user()->code)->update([
                        'code' => Auth::user()->code,
                        'transNo' => $transNoToUse,
                        'contact_no' => $data['contact_no'],
                        'contact_visibility' => $data['contact_visibility'],
                        'email' => Auth::user()->email,
                        'email_visibility' => $data['email_visibility'],
                        'summary' => $data['summary'],
                        'date_birth' => $data['date_birth'],
                        'home_country' => $data['home_country'],
                        'home_state' => $data['home_state'],
                        'current_location' => $data['current_location'],
                        'current_state' => $data['current_state']
                    ]);
                    Usereducation::where('code', Auth::user()->code)->delete();
                    Usercapabilitie::where('code', Auth::user()->code)->delete();
                    Usertraining::where('code', Auth::user()->code)->delete();
                    Userseminar::where('code', Auth::user()->code)->delete();
                    Userskill::where('code', Auth::user()->code)->delete();
                    Useremploymentrecord::where('code', Auth::user()->code)->delete();
                    Usercertificate::where('code', Auth::user()->code)->delete();
                }
                 else{
                    // $photoPath = null;
                   // if ($request->hasFile('photo_pic')) {
                    //    $userCode = Auth::user()->code;
                       // $file = $request//->file('photo_pic');
                        // Define the folder path based on the user's code
                     //   $folderPath = "uploads/{$userCode}/cvphoto";
                        // Store the file and get the stored path
                   //     $photoPath = $file->store($folderPath, 'public');
                    //}
                    UserProfile::create([
                        'code' => Auth::user()->code,
                        'transNo' => $transNoToUse,
                        // 'photo_pic' => $photoPath,
                        'contact_no' => $data['contact_no'],
                        'contact_visibility' => $data['contact_visibility'],
                        'email' => Auth::user()->email,
                        'email_visibility' => $data['email_visibility'],
                        'summary' => $data['summary'],
                        'date_birth' => $data['date_birth'],
                        'home_country' => $data['home_country'],
                        'home_state' => $data['home_state'],
                        'current_location' => $data['current_location'],
                        'current_state' => $data['current_state']
                    ]);

                 }
     
                 // Validate and insert capabilities (languages)
                 if (isset($data['lines']['language'])) {
                     foreach ($data['lines']['language'] as $language) {
                         Usercapabilitie::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'language' => $language['language']
                         ]);
                     }
                 }
     
                 // Validate and insert education data
                 if (isset($data['lines']['education'])) {
                     foreach ($data['lines']['education'] as $education) {
                         Usereducation::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'highest_education' => $education['highest_education'],
                             'school_name' => $education['school_name'],
                             'start_month' => $education['start_month'],
                             'start_year'=> $education['start_year'],
                             'end_month'=> $education['end_month'],
                             'end_year'=> $education['end_year'],
                             'status' => $education['status'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['training'])) {
                     foreach ($data['lines']['training'] as $trainings) {
                         Usertraining::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'training_title' => $trainings['training_title'],
                             'training_provider' => $trainings['training_provider'],
                             'date_completed' => $trainings['trainingdate'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['seminar'])) {
                     foreach ($data['lines']['seminar'] as $seminar) {
                         Userseminar::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'seminar_title' => $seminar['seminar_title'],
                             'seminar_provider' => $seminar['seminar_provider'],
                             'date_completed' => $seminar['seminardate'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['skills'])) {
                     foreach ($data['lines']['skills'] as $skills) {
                         Userskill::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'skills' =>  $skills['skills']
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['employment'])) {
                     foreach ($data['lines']['employment'] as $employment) {
                         Useremploymentrecord::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'company_name' => $employment['company_name'],
                             'position' => $employment['position'],
                             'job_description' => $employment['job_description'],
                             'date_completed' => $employment['date_completed'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['certificate'])) {
                     foreach ($data['lines']['certificate'] as $certificate) {
                         Usercertificate::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'certificate_title' => $certificate['certificate_title'],
                             'certificate_provider' => $certificate['certificate_provider'],
                             'date_completed' => $certificate['date_completed'],
                         ]);
                     }
                 }
     
                 // Update resource data
                 Resource::where('code', Auth::user()->code)
                     ->update([
                         'contact_no' => $data['contact_no'],
                         'date_birth' => $data['date_birth'],
                         'home_country' => $data['home_country'],
                         'current_location' => $data['current_location'],
                    ]);
                     
                    $msg = $exist ? 'updated' : 'saved';
                    // Commit the transaction if everything is successful
                    DB::commit();
                    // Return success response
                    return response()->json([
                        'success' => true,
                        'message' => "Profile and related information " . $msg . " successfully.",
                    ]);
             } catch (\Throwable $th) {
                 // Rollback transaction on error
                 DB::rollBack();
     
                 // Return error response
                 return response()->json([
                     'success' => false,
                     'message' => 'Error occurred: ' . $th->getMessage(),
                ], 500);
            }
        }
     
         // Return unauthorized response if user is not authenticated
         return response()->json([
             'success' => false,
             'message' => 'Unauthorized access.',
        ], 401);
    }
     

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $resource = Resource::where('code', $id)->get();
            
            // return $resource;
    
            $result = [];
            if ($resource) {  
                for ($r = 0; $r < count($resource); $r++) {
                   
                  
        
                     $userprofile = Userprofile::where('code', $resource[$r]->code)->first() ?? null;
                
                    $result= [
                        "btnCurriculum" => $resource[$r]->code == Auth::user()->code ? 1  : '',
                        "email" =>  $userprofile ? ($userprofile->code != Auth::user()->code && $userprofile->email_visibility === 0  ? ' '  : $resource[$r]->email ) : '',
                        "fname" => $resource[$r]->fname,
                        "lname" => $resource[$r]->lname,
                        "photo_pic" => $userprofile->photo_pic ?? 'https://exploredition.com/storage/app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png',
                        "contact_no" => $userprofile ? ($userprofile->code != Auth::user()->code && $userprofile->contact_visibility == 0  ? ' '  : $resource[$r]->contact_no ) : '',
                        "contact_visibility" => $userprofile->contact_visibility ??  null,
                        "email_visibility" => $userprofile->email_visibility ??  null,
                        "summary" => $userprofile->summary ?? null,
                        "date_birth" => $resource[$r]->date_birth,
                        "home_country" =>  $resource[$r]->date_birth,
                        "current_location" => $resource[$r]->current_location,
                        "home_state" => $userprofile->home_state??  null,
                        "current_state" => $userprofile->current_state??  null,
                        "profession" => $resource[$r]->profession,
                        "lines" => [
                            "education" => 
                            $userprofile ?  Usereducation::select('highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year','status')
                                ->where('code', $id)->get() : 'null',
    
                            "language" => Usercapabilitie::select('language')->where('code', $id)->get() ??  'null',
                            
                            "training" => Usertraining::select('training_title', 'training_provider', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                                
                            "seminar" => Userseminar::select('seminar_title', 'seminar_provider', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                                
                                
                            "skills" => Userskill::select('skills')->where('code', $id)
                                ->get() ??  'null',
                            "employment" => Useremploymentrecord::select('company_name', 'position', 'job_description', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                            "certificate" => Usercertificate::where('code', $id)
                                ->get() ??  'null'
                        ]
                    ];
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $result,
                ]);
            } else {
                
                return response()->json([
                    'success' => false,
                    'message' => 'User not defined.',
                ]);
            }
    
        } catch (\Throwable $th) {
            // Return error response if something goes wrong
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $th->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

   

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getProfileData()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not authenticated'
            ]);
        }

        $code = Auth::user()->code;

        $profile = UserProfile::where('code', $code)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $profile->code,
                'transNo' => $profile->transNo ?? null,
                'contact_visibility' => (bool) $profile->contact_visibility,
                'email_visibility' => (bool) $profile->email_visibility,
                'date_birth' => $profile->date_birth,
                'photo_pic' => $profile->photo_pic 
                    ? asset("uploads/{$profile->code}/cvphoto/{$profile->photo_pic}") 
                    : null,
                'home_state' => $profile->home_state,
                'current_state' => $profile->current_state,
                'home_country' => $profile->home_country,
                'current_location' => $profile->current_location,
                'summary' => $profile->summary,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ],
            'message' => 'Profile loaded successfully.'
        ]);
    }

    public function userAuth() {
        if (Auth::check()) {
            $user = Resource::where('code', Auth::user()->code)->first();
            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => $user
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User is not authenticated'
            ]);
        }
    }


    public function userAuthByCode($code)
    {
        if (Auth::check()) {
            $user = DB::table('resources')
                ->leftJoin('userprofiles', 'resources.code', '=', 'userprofiles.code')
                ->select('resources.*', 'userprofiles.photo_pic')
                ->where('resources.code', $code)
                ->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => $user
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User is not authenticated'
        ]);
    }

    // public function userAuthByCode($code) {
    //     if (Auth::check()) {
    //         $user = Resource::where('code', $code)->first();
    //         if ($user) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => $user
    //             ]);
    //         }
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User not found'
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'User is not authenticated'
    //     ]);
    // }




    public function userAuthXX(){
        if (Auth::check()) {
            $user = Resource::where('code',Auth::user()->code)->get();
            // $result = [];
            // for($i = 0 ; $i < count($user); $i++){
            //     $result [] = [
            //         "fullname" => $user[$i]->fullname,
            //         "email" =>  $user[$i]->email,
            //         "fname" => $user[$i]->fname,
            //         "lname" => $user[$i]->lname,
            //         "code" => $user[$i]->code,
            //         "contact_no" => $user[$i]->contact_no,
            //         "profession" => $user[$i]->profession,
            //         "industry" => $user[$i]->industry,
            //         "companywebsite" =>  $user[$i]->companywebsite,
            //         "role_code"=>$user[$i]->role_code,
            //         "company"=>$user[$i]->company,
            //     ];
            // }         
            return response()->json(['success' => true,'message' => $user]);
        } 
        else {
            return response()->json(['success'=>false,'message' => 'User is not authenticated']);
        }
    }

    public function saveProfile(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            $validator = Validator::make($data, [
                'contact_no' => 'nullable|string|max:255',
                'contact_visibility' => 'nullable|boolean',
                'email_visibility' => 'nullable|boolean',
                'date_birth' => 'nullable|string',
                'home_country' => 'nullable|string|max:255', // ✅ validate home_state
                'home_state' => 'nullable|string|max:255',
                'current_state' => 'nullable|string|max:255', // ✅ validate home_state
                'current_location' => 'nullable|string|max:255',
                'photo_pic' => 'nullable|file|image|max:2048',
                'summary' => 'nullable|string|max:255',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            $userCode = Auth::user()->code;
            $email = Auth::user()->email;
            $now = now();
            $photoPath = null;
            $formattedDateBirth = null;
            if (!empty($data['date_birth'])) {
                try {
                    $formattedDateBirth = Carbon::createFromFormat('n/j/Y', $data['date_birth'])->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use MM/DD/YYYY format.',
                    ], 422);
                }
            }

            if ($request->hasFile('photo_pic')) {
                $file = $request->file('photo_pic');
                $uuid = Str::uuid();
                $folderPath = "uploads/{$userCode}/cvphoto/{$uuid}";
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs($folderPath, $fileName, 'public');
                $photoPath = "https://exploredition.com/storage/app/public/{$folderPath}/{$fileName}";
            }

            $contactVisibility = isset($data['contact_visibility']) ? (bool) $data['contact_visibility'] : false;
            $emailVisibility = isset($data['email_visibility']) ? (bool) $data['email_visibility'] : false;

            $exists = UserProfile::where('code', $userCode)->first();

            if ($exists) {
                if (!$exists->photo_pic && !$photoPath) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a profile photo to complete your profile.',
                    ], 404);
                }

                $updateData = [
                    'contact_no' => $data['contact_no'] ?? null,
                    'contact_visibility' => $contactVisibility,
                    'email' => $email,
                    'email_visibility' => $emailVisibility,
                    'date_birth' => $formattedDateBirth,
                    'home_state' => $data['home_state'] ?? null,
                    'home_country' => $data['home_country'] ?? null,
                    'current_state' => $data['current_state'] ?? null,
                    'current_location' => $data['current_location'] ?? null,
                    'summary' => $data['summary'] ?? null,
                    'updated_at' => $now,
                ];
                if ($photoPath) {
                    $updateData['photo_pic'] = $photoPath;
                }

                UserProfile::where('code', $userCode)->update($updateData);

            } else {
                if (!$photoPath) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a photo before saving your profile.',
                    ], 422);
                }

                $transNo = UserProfile::max('transNo');
                $newTrans = empty($transNo) ? 1 : $transNo + 1;

                UserProfile::insert([
                    'code' => $userCode,
                    'transNo' => $newTrans,
                    'contact_no' => $data['contact_no'] ?? null,
                    'contact_visibility' => $contactVisibility,
                    'email' => $email,
                    'email_visibility' => $emailVisibility,
                    'date_birth' => $formattedDateBirth,
                    'home_state' => $data['home_state'],
                    'home_country' => $data['home_country'],
                    'current_state' => $data['current_state'] ?? null,
                    'current_location' => $data['current_location'] ?? null,
                    'photo_pic' => $photoPath,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('resources')->where('code', $userCode)->update([
                'contact_no' => $data['contact_no'] ?? null,
                'date_birth' => $formattedDateBirth,
                'updated_at' => $now,
            ]);

            DB::commit();

            $profile = UserProfile::where('code', $userCode)->first();
            return response()->json([
                'success' => true,
                'message' => $exists ? 'Profile updated successfully.' : 'Profile created successfully.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function getProfileByCode()
    {
        try {
            $code = Auth::user()->code;
            $profile = DB::table('userprofiles as u')
                ->leftJoin('resources as r', 'u.code', '=', 'r.code')
                ->where('u.code', $code)
                ->select(
                    'u.code',
                    'u.contact_no',
                    'u.contact_visibility',
                    'u.email',
                    'u.email_visibility',
                    'u.date_birth',
                    'u.photo_pic',
                    'r.profession',
                    'r.company',
                    'r.industry',
                    'u.created_at',
                    'u.updated_at'
                )
                ->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $profile,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function getProfileCV()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'User is not authenticated']);
        }

         $currentUserCode = Auth::user()->code;

        // ✅ Get all user profiles for the logged-in user
        $userProfiles = DB::table('userprofiles')->where('code', $currentUserCode)->get();

        // ✅ Get common resource info once
        $resource = DB::table('resources')
            ->select('fname', 'lname', 'email', 'profession')
            ->where('code', $currentUserCode)
            ->first();

        $result = [];

        foreach ($userProfiles as $profile) {
            $result[] = [
                "code" => $currentUserCode,
                "email" => $resource->email ?? null,
                "fname" => $resource->fname ?? null,
                "lname" => $resource->lname ?? null,
                "photo_pic" => $profile->photo_pic ?? 'https://exploredition.com/storage/app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png',
                "contact_no" => $profile->contact_no,
                "contact_visibility" => $profile->contact_visibility,
                "email_visibility" => $profile->email_visibility,
                "summary" => $profile->summary,
                "date_birth" => $profile->date_birth,
                "home_country" => $profile->home_country,
                "current_location" => $profile->current_location,
                "home_state" => $profile->home_state,
                "current_state" => $profile->current_state,
                "profession" => $resource->profession ?? null,
                "lines" => [
                    "education" => DB::table('usereducations')
                        ->select('highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year', 'status')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "language" => DB::table('usercapabilities')
                        ->select('language')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "training" => DB::table('usertrainings')
                        ->select('training_title', 'training_provider', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "seminar" => DB::table('userseminars')
                        ->select('seminar_title', 'seminar_provider', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "skills" => DB::table('userskills')
                        ->select('skills')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "employment" => DB::table('useremploymentrecords')
                        ->select('company_name', 'position', 'job_description', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "certificate" => DB::table('usercertificates')
                        ->where('code', $currentUserCode)
                        ->get()
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $result
        ]);
    }




}



//    *** STORE ***
// {
//     "photo_pic": null,
//     "contact_no": "1234567890",
//     "contact_visibility": 1,
//     "email_visibility": 1,
//     "summary": "This is a summary.",
//     "date_birth": "1990-01-01",
//     "home_country": "United States",
//     "current_location": "New York",
//     "home_state": "NY",
//     "current_state": "NY",
//     "lines": {
//       "education": [
//         {
//           "highest_education": "Bachelor's Degree",
//           "school_name": "University A",
//           "start_month": "January",
//           "start_year": "2010",
//           "end_month": "May",
//           "end_year": "2014",
//           "status": "Completed"
//         },
//         {
//           "highest_education": "Master's Degree",
//           "school_name": "University B",
//           "start_month": "August",
//           "start_year": "2015",
//           "end_month": "June",
//           "end_year": "2017",
//           "status": "Completed"
//         }
//       ],
//       "language": [
//         {
//           "language": "English"
//         },
//         {
//           "language": "French"
//         }
//       ],
//       "training": [
//         {
//           "training_title": "Leadership Training",
//           "training_provider": "Training Corp",
//           "trainingdate": "2019-12-01"
//         }
//       ],
//       "seminar": [
//         {
//           "seminar_title": "Tech Conference",
//           "seminar_provider": "TechWorld",
//           "seminardate": "2022-10-15"
//         }
//       ],
//     "skills": [
//         { "skills": "Programming" },
//         { "skills": "Web Design" }
//     ],
//       "employment": [
//         {
//           "company_name": "Company A",
//           "position": "Software Engineer",
//           "job_description": "Developed software solutions.",
//           "date_completed": "2021-08-01"
//         }
//       ],
//       "certificate": [
//         {
//           "certificate_title": "AWS Certified",
//           "certificate_provider": "Amazon",
//           "date_completed": "2020-05-01"
//         }
//       ]
//     }
// }