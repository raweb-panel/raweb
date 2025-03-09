<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Port;

class PortController extends Controller
{
    public function index()
    {
        $ports = Port::orderBy('port_number')->get();
        return view('ports.index', compact('ports'));
    }

    public function api_index()
    {
        $Ports = Port::all();
        return response()->json($Ports);
    }

    public function create()
    {
        return view('ports.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'port_number' => 'required|integer|unique:ports',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:http,ssl',
        ]);

        try {
            $port = Port::create($request->only('port_number', 'description', 'type'));
            return response()->json(['message' => 'Port added successfully.', 'port' => $port], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add port: ' . $e->getMessage()], 500);
        }
    }

    public function edit(Port $port)
    {
        return view('ports.edit', compact('port'));
    }

    public function update(Request $request, $id)
    {
        $port = Port::findOrFail($id);

        $request->validate([
            'port_number' => 'required|integer|unique:ports,port_number,' . $port->id,
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:http,ssl',
        ]);

        try {
            $port->update($request->only('port_number', 'description', 'type'));
            return response()->json(['message' => 'Port updated successfully.', 'port' => $port], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update port: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $port = Port::findOrFail($id);
            $port->delete();
            return response()->json(['message' => 'Port deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete port: ' . $e->getMessage()], 500);
        }
    }
}
