<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class PresenceController extends Controller
{
    private $base_url = 'http://36.92.181.10:9981/sisbe/';
    private $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyaWQiOiIxNSIsInJvbGUiOiJhZG1pbiIsInJ1bGVzIjoiMyIsImxvZyI6MjYwLCJ0aW1lIjoiVHVlLCAxMS0wNC0yMDIzIC0gMTk6Mzg6MTcgV0lCIiwiZGVwdGlkIjpudWxsfQ.0dTSNeWS3vC76TdtQe3ZqnnaCjOiL-qIvKVKmy0LDxA';

    public function index()
    {
        $limit = request('limit', 10);
        $offset = request('offset', 0);

        try {
            $presence = Presence::with(['presenceTrans'])
                                ->skip($offset)
                                ->take($limit)
                                ->get();

            $response = $presence->isEmpty()
                ? ['message' => 'Data not found', 'success' => true, 'data' => []]
                : ['data' => $presence, 'message' => 'Success to Fetch All Datas', 'success' => true];

            return response()->json($response, 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            // return response()->json(['info' => $ex->getMessage(),'message' => 'Failed to Retrieve Data', 'success' => false], 500);
            return response()->json(['message' => 'Failed to Retrieve Data', 'success' => false], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dates' => 'required',
            'dept' => 'required',
            'academic_year' => 'required',
            'semester' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'success' => false
            ], 400);
        }

        $dept = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'dept/get/' . $request->dept)->json();
        $semester = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'semesters/get/' . $request->semester)->json();
        $financial_year = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'financial_year/get/' . $request->academic_year)->json();

        if (empty($dept['data'])) {
            return response()->json([
                'message' => 'Department not found.',
                'success' => false
            ], 404);
        }

        if (empty($semester['data'])) {
            return response()->json([
                'message' => 'semester not found.',
                'success' => false
            ], 404);
        }
        if (empty($financial_year['data'])) {
            return response()->json([
                'message' => 'financial_year not found.',
                'success' => false
            ], 404);
        }

        $data = Presence::create([
            'dates' => $request->dates,
            'dept' => $dept['data']['id'],
            'academic_year' => $financial_year['data']['year'],
            'semester' => $semester['data']['id'],
            'approved' => '0',
        ]);

        return response()->json([
            'data' => $data,
            'message' => 'Data created successfully.',
            'success' => true
        ], 201);
    }

    public function show($id)
    {
        try {
            $presence = Presence::with(['presenceTrans'])->findOrFail($id);

            return response()->json([
                'data' => $presence,
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
        $data = Presence::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record not found.',
                'success' => false
            ], 404);
        }

        $dept = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'dept/get/' . $request->dept)->json();
        $semester = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'semesters/get/' . $request->semester)->json();
        $financial_year = Http::withHeaders(['X-Auth-Token' => $this->token])->get($this->base_url . 'financial_year/get/' . $request->academic_year)->json();

        $validator = Validator::make($request->all(), [
            'dates' => 'required',
            'dept' => 'required',
            'academic_year' => 'required',
            'semester' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [],
                'message' => $validator->errors(),
                'success' => false
            ]);
        }

        if (empty($dept['data']) || empty($semester['data']) || empty($financial_year['data'])) {
            return response()->json([
                'message' => 'Data not found.',
                'success' => false
            ], 404);
        }

        $data->update([
            'dates' => $request->dates,
            'dept' => $dept['data']['id'],
            'academic_year' => $financial_year['data']['year'],
            'semester' => $semester['data']['id'],
            'approved' => '0',
        ]);

        return response()->json([
            'data' => $data,
            'message' => 'Data updated successfully.',
            'success' => true
        ], 201);
    }

    public function toggleApproved($id)
    {
        $data = Presence::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record not found.',
                'success' => false
            ], 404);
        }

        $newApprovedValue = $data->approved == 1 ? 0 : 1;

        $data->update([
            'approved' => $newApprovedValue,
        ]);

        $message = $newApprovedValue == 1 ? 'Data Posted Successfully' : 'Data Unposted Successfully';

        return response()->json([
            'data' => [],
            'message' => $message,
            'success' => true
        ]);
    }

    public function destroy($id)
    {
        $data = Presence::find($id);

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
    }
}
