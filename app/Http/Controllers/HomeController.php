<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function getNginxStatus(Request $request)
    {
        // Fetch Nginx stub URL from the settings.
        $nginxStubUrl = Setting::where('key', 'nginx_stub_url')->value('value');

        try {
            // Get Nginx status.
            $response = Http::get($nginxStubUrl);
            $nginxStatus = $response->body();
            $parsedStatus = $this->parseNginxStatus($nginxStatus);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to fetch Nginx status'], 500);
        }

        // Get RAM and CPU usage.
        $ramUsage = $this->getRamUsage();
        $cpuUsage = $this->getCpuUsage();

        // Get Network Usage (Mb/s).
        $networkUsage = $this->getNetworkUsage();

        // Merge system metrics into the parsed status.
        $parsedStatus['ram_usage'] = $ramUsage;
        $parsedStatus['cpu_usage'] = $cpuUsage;
        $parsedStatus['bits_in']   = $networkUsage['bits_in'];
        $parsedStatus['bits_out']  = $networkUsage['bits_out'];

        return Response::json($parsedStatus);
    }

    /**
     * Returns the top 10 IPs by connection count and simulates bandwidth (Mb/s).
     */
    public function getTopIpsData(Request $request)
    {
        $ipStats = [];
        exec("netstat -ntu", $output);
        
        foreach ($output as $index => $line) {
            // Skip the header line
            if ($index === 0) {
                continue;
            }
            
            // Process lines starting with TCP or UDP
            if (preg_match('/^(tcp|udp)/i', $line, $matches)) {
                $type = strtolower($matches[1]);
                $parts = preg_split('/\s+/', trim($line));
                
                // Ensure we have at least 5 columns
                if (count($parts) < 5) {
                    continue;
                }
                
                // Extract the local address (server's IP:port) and get the port
                $local = $parts[3] ?? '';
                $localParts = explode(":", $local);
                if (count($localParts) < 2) {
                    continue;
                }
                $localPort = $localParts[1];
                
                // Extract the foreign address (client's IP:port) and get the IP
                $foreign = $parts[4] ?? '';
                $foreignParts = explode(":", $foreign);
                if (count($foreignParts) < 2) {
                    continue;
                }
                $remoteIp = $foreignParts[0];
                
                // Ignore invalid or local addresses
                if (!filter_var($remoteIp, FILTER_VALIDATE_IP) || $remoteIp === '127.0.0.1' || $remoteIp === '127.0.0.53' || $remoteIp === '::1' || $remoteIp === $_SERVER['SERVER_ADDR']) {
                    continue;
                }
                
                // Group data by remote IP, and record the local port(s) they're connecting to.
                if (!isset($ipStats[$remoteIp])) {
                    $ipStats[$remoteIp] = ['connections' => 0, 'ports' => [], 'type' => $type];
                }
                
                $ipStats[$remoteIp]['connections']++;
                if (!in_array($localPort, $ipStats[$remoteIp]['ports'])) {
                    $ipStats[$remoteIp]['ports'][] = $localPort;
                }
            }
        }
        
        // Sort descending by connection count.
        uasort($ipStats, function ($a, $b) {
            return $b['connections'] - $a['connections'];
        });
        
        // Limit to top 10 remote IPs.
        $topIps = array_slice($ipStats, 0, 10, true);
        
        return response()->json($topIps);
    }
    
    
    
    

    private function parseNginxStatus($status)
    {
        $lines = explode("\n", $status);
        $parsedStatus = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/Active connections:\s+(\d+)/i', $line, $matches)) {
                $parsedStatus['active_connections'] = (int)$matches[1];
            } elseif (preg_match('/^\s*(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                $parsedStatus['accepts']  = (int)$matches[1];
                $parsedStatus['handled']  = (int)$matches[2];
                $parsedStatus['requests'] = (int)$matches[3];
            } elseif (preg_match('/Reading:\s+(\d+)\s+Writing:\s+(\d+)\s+Waiting:\s+(\d+)/i', $line, $matches)) {
                $parsedStatus['reading'] = (int)$matches[1];
                $parsedStatus['writing'] = (int)$matches[2];
                $parsedStatus['waiting'] = (int)$matches[3];
            }
        }

        return $parsedStatus;
    }

    /**
     * Get RAM usage as a percentage.
     */
    private function getRamUsage()
    {
        $ramUsage = 'N/A';
        exec("free -m", $freeOutput);
        foreach ($freeOutput as $line) {
            if (strpos($line, 'Mem:') === 0) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 7) {
                    $total     = (float)$parts[1];
                    $available = (float)$parts[6];
                    if ($total > 0) {
                        $usagePercent = (1 - $available / $total) * 100;
                        $ramUsage = round($usagePercent, 1) . '%';
                    }
                }
                break;
            }
        }
        return $ramUsage;
    }

    /**
     * Get CPU usage percentage.
     */
    private function getCpuUsage()
    {
        $cpuUsage = 'N/A';
        exec("top -bn1 | grep 'Cpu(s)'", $cpuOutput);
        if (!empty($cpuOutput)) {
            if (preg_match('/(\d+\.\d+)\s*id/', $cpuOutput[0], $matches)) {
                $idle = (float)$matches[1];
                $cpuUsage = round(100 - $idle, 1) . '%';
            }
        }
        return $cpuUsage;
    }

    /**
     * Get Network usage in Mb/s.
     */
    private function getNetworkUsage()
    {
        $totalReceived = 0;
        $totalTransmitted = 0;
        exec("cat /proc/net/dev", $netOutput);
        foreach ($netOutput as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($iface, $data) = explode(":", $line, 2);
            $iface = trim($iface);
            if ($iface === 'lo') {
                continue;
            }
            $fields = preg_split('/\s+/', trim($data));
            if (count($fields) >= 9) {
                $totalReceived += (float)$fields[0];
                $totalTransmitted += (float)$fields[8];
            }
        }

        $currentTime = microtime(true);
        $currentData = [
            'timestamp'   => $currentTime,
            'received'    => $totalReceived,
            'transmitted' => $totalTransmitted,
        ];

        $cacheKey = 'network_usage_last';
        $prevData = Cache::get($cacheKey);
        Cache::put($cacheKey, $currentData, 10);

        if (!$prevData) {
            return ['bits_in' => 0, 'bits_out' => 0];
        }

        $timeDiff = $currentData['timestamp'] - $prevData['timestamp'];
        if ($timeDiff <= 0) {
            $timeDiff = 1;
        }

        $diffReceived = $currentData['received'] - $prevData['received'];
        $diffTransmitted = $currentData['transmitted'] - $prevData['transmitted'];

        // Convert bytes difference to bits, then to megabits, and divide by time (Mb/s).
        $mbIn  = ($diffReceived * 8) / 1000000 / $timeDiff;
        $mbOut = ($diffTransmitted * 8) / 1000000 / $timeDiff;

        return [
            'bits_in'  => round($mbIn, 1),
            'bits_out' => round($mbOut, 1)
        ];
    }

    private function getNetworkUsageByIp()
    {
        $ipUsage = [];
        exec("cat /proc/net/dev", $netOutput);
        foreach ($netOutput as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($iface, $data) = explode(":", $line, 2);
            $iface = trim($iface);
            if ($iface === 'lo') {
                continue;
            }
            $fields = preg_split('/\s+/', trim($data));
            if (count($fields) >= 9) {
                $received = (float)$fields[0];
                $transmitted = (float)$fields[8];
                $ipUsage[$iface] = [
                    'received' => $received,
                    'transmitted' => $transmitted,
                ];
            }
        }

        $currentTime = microtime(true);
        $currentData = [
            'timestamp' => $currentTime,
            'usage' => $ipUsage,
        ];

        $cacheKey = 'network_usage_by_ip_last';
        $prevData = Cache::get($cacheKey);
        Cache::put($cacheKey, $currentData, 10);

        if (!$prevData) {
            return [];
        }

        $timeDiff = $currentData['timestamp'] - $prevData['timestamp'];
        if ($timeDiff <= 0) {
            $timeDiff = 1;
        }

        $networkUsage = [];
        foreach ($currentData['usage'] as $iface => $data) {
            if (isset($prevData['usage'][$iface])) {
                $diffReceived = $data['received'] - $prevData['usage'][$iface]['received'];
                $diffTransmitted = $data['transmitted'] - $prevData['usage'][$iface]['transmitted'];

                // Convert bytes difference to bits, then to megabits, and divide by time (Mb/s).
                $mbIn = ($diffReceived * 8) / 1000000 / $timeDiff;
                $mbOut = ($diffTransmitted * 8) / 1000000 / $timeDiff;

                $networkUsage[$iface] = [
                    'bits_in' => round($mbIn, 1),
                    'bits_out' => round($mbOut, 1),
                ];
            }
        }

        return $networkUsage;
    }
}
