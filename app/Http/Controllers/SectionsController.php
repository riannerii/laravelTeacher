<?php

namespace App\Http\Controllers;

use App\Models\sections;
use App\Http\Requests\StoresectionsRequest;
use App\Http\Requests\UpdatesectionsRequest;

class SectionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoresectionsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(sections $sections)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatesectionsRequest $request, sections $sections)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(sections $sections)
    {
        //
    }
}