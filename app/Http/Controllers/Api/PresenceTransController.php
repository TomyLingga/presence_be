<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresenceTrans;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;


class PresenceTransController extends Controller
{
    private $base_url = 'http://36.92.181.10:9981/sisbe/';
    private $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxNSIsInJvbGUiOiJhZG1pbiIsInJ1bGVzIjoiMyIsImxvZyI6MjYwLCJ0aW1lIjoiVHVlLCAxMS0wNC0yMDIzIC0gMTk6Mzg6MTcgV0lCIiwiZGVwdGlkIjpudWxsfQ.0dTSNeWS3vC76TdtQe3ZqnnaCjOiL-qIvKVKmy0LDxA';

    public function index()
    {
        $limit = request('limit', 10);
        $offset = request('offset', 0);

        try {
            $presence_trans = PresenceTrans::skip($offset)
                                ->take($limit)
                                ->get();

            $response = $presence_trans->isEmpty()
                ? ['message' => 'Data not found', 'success' => true, 'data' => []]
                : ['data' => $presence_trans, 'message' => 'Success to Fetch All Datas', 'success' => true];

            return response()->json($response, 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            // return response()->json(['info' => $ex->getMessage(),'message' => 'Failed to Retrieve Data', 'success' => false], 500);
            return response()->json(['message' => 'Failed to Retrieve Data', 'success' => false], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required',
            'presence_id' => 'required',
            'status' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'success' => false
            ], 400);
        }

        $presence = Presence::find($request->presence_id);

        // dd($presence->approved);

        if (empty($presence)) {
            return response()->json([
                'message' => 'Presence not found.',
                'success' => false
            ], 404);
        }

        if ($presence->approved == '1') {
            return response()->json([
                'message' => 'Presence already approved.',
                'success' => false
            ], 404);
        }

        $existingRecord = PresenceTrans::where([
            'student_id' => $request->student_id,
            'presence_id' => $request->presence_id,
        ])->first();

        if (!empty($existingRecord)) {
            return response()->json([
                'message' => 'File already exists.',
                'success' => false
            ], 400);
        }

        $student = Http::withHeaders(['X-Auth-Token' => $this->token])
                    ->get($this->base_url . 'students/get/' . $request->student_id)
                    ->json();

        if (empty($student['data'])) {
            return response()->json([
                'message' => 'Student not found.',
                'success' => false
            ], 404);
        }

        $status_arr = ['attend', 'permission', 'sick', 'absent', 'leaves'];
        $status = $status_arr[$request->status - 1];
        $attributes = array_fill_keys($status_arr, 0);
        $attributes[$status] = 1;

        // attend 1, permission 2, sick 3, absent 4, leaves 5

        $description = ($attributes['attend'] == 1) ? '-' : $request->description;

        $data = PresenceTrans::create([
            'student_id' => $request->student_id,
            'presence_id' => $request->presence_id,
            'checkin' => ($attributes['attend'] == 1) ? now() : null,
            'description' => $description,
        ] + $attributes);

        return response()->json([
            'data' => $data,
            'message' => 'Data created successfully.',
            'success' => true
        ], 201);
    }

    public function show($id)
    {
        try {
            $presenceT = PresenceTrans::with(['presence'])->findOrFail($id);

            $student = Http::withHeaders(['X-Auth-Token' => $this->token])
                    ->get($this->base_url . 'students/get/' . $presenceT->student_id)
                    ->json();

            // dd($presenceT->student_id);
            // dd($student);

            $presenceT->student = $student['data'];

            return response()->json([
                'data' => $presenceT,
                'message' => 'Data Found',
                'success' => true
            ], 200);
        } catch (\Exception $ex) {
            $statusCode = ($ex instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) ? 404 : 500;
            $message = ($statusCode === 404) ? 'Data not found' : 'Failed to Retrieve Data';

            return response()->json([
                'message' => $message,
                'success' => false
            ], $statusCode);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required',
            'presence_id' => 'required',
            'status' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'success' => false
            ], 400);
        }

        $presenceTrans = PresenceTrans::findOrFail($id);

        if (!$presenceTrans) {
            return response()->json([
                'message' => 'presenceTrans not found.',
                'success' => false
            ], 404);
        }

        $presence = Presence::find($request->presence_id);

        if (empty($presence)) {
            return response()->json([
                'message' => 'Presence not found.',
                'success' => false
            ], 404);
        }

        $student = Http::withHeaders(['X-Auth-Token' => $this->token])
                    ->get($this->base_url . 'students/get/' . $request->student_id)
                    ->json();

        if (empty($student['data'])) {
            return response()->json([
                'message' => 'Student not found.',
                'success' => false
            ], 404);
        }

        $status_arr = ['attend', 'permission', 'sick', 'absent', 'leaves'];
        $status = $status_arr[$request->status - 1];
        $attributes = array_fill_keys($status_arr, 0);
        $attributes[$status] = 1;

        // attend 1, permission 2, sick 3, absent 4, leaves 5

        $description = ($attributes['attend'] == 1) ? '-' : $request->description;

        $presenceTrans->update([
            'student_id' => $request->student_id,
            'presence_id' => $request->presence_id,
            'checkin' => ($attributes['attend'] == 1) ? now() : null,
            'description' => $description,
        ] + $attributes);

        return response()->json([
            'data' => $presenceTrans->fresh(),
            'message' => 'Data updated successfully.',
            'success' => true
        ], 200);
    }

    public function destroy($id)
    {
        try{
            $data = PresenceTrans::find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Record not found.',
                    'success' => false
                ], 404);
            }

            $data->delete();

            return response()->json([
                'data' => [],
                'message' => 'Data Deleted Successfully',
                'success' => true
            ]);
        }catch (\Illuminate\Database\QueryException $ex) {
            return response()->json(['info' => 'Failed to Delete Data']);
        }
    }
}
