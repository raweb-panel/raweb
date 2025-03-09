<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    protected $logConfigPath = '/nginx/config/logs.conf';

    public function index()
    {
        $logs = [];
        if (File::exists($this->logConfigPath)) {
            $contents = File::get($this->logConfigPath);
            $logFormats = explode("\n", $contents);
            foreach ($logFormats as $index => $format) {
                if (trim($format)) {
                    $logs[] = ['id' => $index, 'format' => $format];
                }
            }
        }
        return response()->json(['logs' => $logs]);
    }

    public function index_names()
    {
        $logs = [];
        if (File::exists($this->logConfigPath)) {
            $contents = File::get($this->logConfigPath);
            preg_match_all('/log_format\s+(\w+)/', $contents, $matches);
            $logs = $matches[1];
        }
        return response()->json(['logs' => $logs]);
    }
    
    public function store(Request $request)
    {
        $request->validate(['format' => 'required|string']);
        $format = $request->input('format');
        File::append($this->logConfigPath, $format . "\n");
        return response()->json(['status' => 'success']);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['format' => 'required|string']);
        $format = $request->input('format');
        if (File::exists($this->logConfigPath)) {
            $contents = File::get($this->logConfigPath);
            $logFormats = explode("\n", $contents);
            if (isset($logFormats[$id])) {
                $logFormats[$id] = $format;
                File::put($this->logConfigPath, implode("\n", $logFormats));
            }
        }
        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        if (File::exists($this->logConfigPath)) {
            $contents = File::get($this->logConfigPath);
            $logFormats = explode("\n", $contents);
            if (isset($logFormats[$id])) {
                unset($logFormats[$id]);
                File::put($this->logConfigPath, implode("\n", $logFormats));
            }
        }
        return response()->json(['status' => 'success']);
    }
    public function stream_names()
    {
        $logs = [];
        if (File::exists($this->logConfigPath)) {
            $contents = File::get($this->logConfigPath);
            preg_match_all('/\$([a-zA-Z_]+)/', $contents, $matches);
            $logs = array_unique($matches[1]);
        }
        return response()->json(['logs' => $logs]);
    }
    public function streamLogs(Request $request)
    {
        $vhostId = $request->query('vhost');
        $vhostModel = \App\Models\Vhost::find($vhostId);
        if (!$vhostModel) {
            return response()->json(['error' => 'Invalid vhost.'], 400);
        }
        $vhost = $vhostModel->server_name;
        $filters = $request->query('filters') ? explode(',', $request->query('filters')) : [];
    
        $logFilePath = "/srv/{$vhost}/logs/access.log";
        if (!file_exists($logFilePath)) {
            return response()->json(['error' => 'Log file not found.'], 404);
        }
        // TO-DO: Implement the WebSocket server logic here

        $response = 'This feature is not yet implemented.';
        return $response;
    }
    
    
    

    private function parseLogLine($line, $filters)
    {
        // If no filters are set, return the entire line.
        if (empty($filters)) {
            return $line;
        }
        // Check if the log line contains all provided filter strings.
        foreach ($filters as $filter) {
            if (stripos($line, trim($filter)) === false) {
                return '';
            }
        }
        return $line;
    }
}