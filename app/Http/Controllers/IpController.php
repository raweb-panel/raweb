<?php

namespace App\Http\Controllers;

use App\Models\Ip;
use Illuminate\Http\Request;

class IpController extends Controller
{
    /**
     * Display a listing of the IP addresses.
     */
    //public function index()
    //{
    //    $ips = Ip::orderBy('ip_address')->get();
    //    return view('ips.index', compact('ips'));
    //}

    // List IPs (return JSON for Vue)
    public function api_index()
    {
        $Ips = Ip::all();
        return response()->json($Ips);
    }

    /**
     * Show the form for creating a new IP address.
     */
    public function create()
    {
        return view('ips.create');
    }

    /**
     * Store a newly created IP address in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ip_address'  => 'required|ip|unique:ips,ip_address',
            'type'        => 'required|in:ipv4,ipv6',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $ip = Ip::create($request->only('ip_address', 'type', 'description'));
            return response()->json(['message' => 'IP added successfully.', 'ip' => $ip], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add IP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified IP address in storage.
     */
    public function update(Request $request, $id)
    {
        $ip = Ip::findOrFail($id);
    
        $request->validate([
            'ip_address'  => 'required|ip|unique:ips,ip_address,' . $ip->id,
            'type'        => 'required|in:ipv4,ipv6',
            'description' => 'nullable|string|max:255',
        ]);
    
        try {
            $ip->update($request->only('ip_address', 'type', 'description'));
            return response()->json(['message' => 'IP updated successfully.', 'ip' => $ip], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update IP: ' . $e->getMessage()], 500);
        }
    }
    

    /**
     * Remove the specified IP address from storage.
     */
    public function destroy($id)
    {
        try {
            $ip = Ip::findOrFail($id);
            $ip->delete();
            return response()->json(['message' => 'IP deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete IP: ' . $e->getMessage()], 500);
        }
    }
}
