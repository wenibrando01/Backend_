<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolDay;
use Illuminate\Http\Request;

class SchoolDayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $days = SchoolDay::orderBy('date', 'desc')
            ->paginate(30);

        return response()->json($days);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $day = SchoolDay::findOrFail($id);

        return response()->json($day);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
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
}
