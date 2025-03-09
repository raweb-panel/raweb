<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vhost;
use App\Models\PhpFpmPool;

class VhostController extends Controller
{
    protected $directory = '/nginx/live';

    // List vhosts (return JSON for Vue)
    public function index()
    {
        $vhosts = Vhost::all();
        return response()->json($vhosts);
    }

    // Store a new vhost (Vue API)
    public function store(Request $request)
    {
        try {
            // Validate including the ip fields.
            $validatedData = $request->validate([
                'file'        => 'required|max:191|min:1|unique:vhosts,file',
                'server_name' => 'required|max:191|min:1',
                'http_port'   => 'required|integer',
                'ssl_port'    => 'nullable|integer',
                'php_version' => 'sometimes|required|max:10|min:1',
                'log_type'    => 'sometimes|required',
                'ipv4'        => 'nullable|string',
                'ipv6'        => 'nullable|string'
            ]);

            // Only set the ip if a non-empty value other than 'none' is provided.
            $ipv4 = (!empty($validatedData['ipv4']) && $validatedData['ipv4'] !== 'none')
                        ? $validatedData['ipv4']
                        : null;
            $ipv6 = (!empty($validatedData['ipv6']) && $validatedData['ipv6'] !== 'none')
                        ? $validatedData['ipv6']
                        : null;

            $vhost = Vhost::create([
                'file'        => $validatedData['file'],
                'server_name' => $validatedData['server_name'],
                'http_port'   => $validatedData['http_port'],
                'ssl_port'    => $validatedData['ssl_port'] ?? null,
                'php_version' => $validatedData['php_version'] ?? '8.4',
                'log_type'    => $validatedData['log_type'] ?? 'main',
                'ipv4'        => $ipv4,
                'ipv6'        => $ipv6,
            ]);

            $this->generateConfig($vhost);
            $this->create_home($vhost->server_name);
            $result = $this->reloadNginx();

            return response()->json([
                'status' => 'success',
                'vhost'  => $vhost,
                'nginx'  => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing vhost
    public function update(Request $request, $id)
    {
        try {
            $vhost = Vhost::findOrFail($id);

            $validatedData = $request->validate([
                'server_name' => 'required|max:191|min:1',
                'http_port'   => 'required|integer',
                'ssl_port'    => 'nullable|integer',
                'php_version' => 'sometimes|required|max:10|min:1',
                'log_type'    => 'sometimes|required',
                'ipv4'        => 'nullable|string',
                'ipv6'        => 'nullable|string'
            ]);

            $ipv4 = (!empty($validatedData['ipv4']) && $validatedData['ipv4'] !== 'none')
                        ? $validatedData['ipv4']
                        : null;
            $ipv6 = (!empty($validatedData['ipv6']) && $validatedData['ipv6'] !== 'none')
                        ? $validatedData['ipv6']
                        : null;

            $vhost->update([
                'server_name' => $validatedData['server_name'],
                'http_port'   => $validatedData['http_port'],
                'ssl_port'    => $validatedData['ssl_port'] ?? $vhost->ssl_port,
                'php_version' => $validatedData['php_version'] ?? $vhost->php_version,
                'log_type'    => $validatedData['log_type'] ?? $vhost->log_type,
                'ipv4'        => $ipv4,
                'ipv6'        => $ipv6,
            ]);

            $this->generateConfig($vhost);
            $result = $this->reloadNginx();

            return response()->json([
                'status' => 'success',
                'vhost'  => $vhost,
                'nginx'  => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a vhost (API)
    public function destroy($id)
    {
        try {
            $vhost = Vhost::findOrFail($id);
            $filePath = $this->directory . '/' . $vhost->file . '.conf';
            $vhostDir = '/srv/' . $vhost->server_name;

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            if (is_dir($vhostDir)) {
                $this->recursiveDelete($vhostDir);
            }

            $vhost->delete();
            $result = $this->reloadNginx();

            return response()->json([
                'status'  => 'success',
                'message' => 'Vhost deleted, directory removed, and Nginx reloaded!',
                'nginx'   => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Recursively delete a directory
    private function recursiveDelete($dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $this->recursiveDelete($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }

    // Generate the nginx config file
    private function generateConfig($vhost)
    {
        $content = "server {\n";

        // Add listen directives for each IP if it exists.
        if ($vhost->ipv4) {
            $content .= "    listen {$vhost->ipv4}:{$vhost->http_port};\n";
            if ($vhost->ssl_port) {
                $content .= "    listen {$vhost->ipv4}:{$vhost->ssl_port} ssl;\n";
                $content .= "    listen {$vhost->ipv4}:{$vhost->ssl_port} quic;\n";
            }
        }
        if ($vhost->ipv6) {
            $content .= "    listen [{$vhost->ipv6}]:{$vhost->http_port};\n";
            if ($vhost->ssl_port) {
                $content .= "    listen [{$vhost->ipv6}]:{$vhost->ssl_port} ssl;\n";
                $content .= "    listen [{$vhost->ipv6}]:{$vhost->ssl_port} quic;\n";
            }
        }
        // Fallback if no IP is provided.
        if (!$vhost->ipv4 && !$vhost->ipv6) {
            $content .= "    listen {$vhost->http_port};\n";
            if ($vhost->ssl_port) {
                $content .= "    listen {$vhost->ssl_port} ssl;\n";
                $content .= "    listen {$vhost->ssl_port} quic;\n";
            }
        }

        $content .= "    server_name {$vhost->server_name} www.{$vhost->server_name};\n";
        $content .= "    root /srv/{$vhost->server_name}/public_html;\n";
        $content .= "    index index.html index.htm index.php;\n\n";

        $content .= "    include /srv/{$vhost->server_name}/config/nginx/locations/root.conf;\n";
        $content .= "    include /srv/{$vhost->server_name}/config/nginx/locations/redirects.conf;\n";
        $content .= "    include /srv/{$vhost->server_name}/config/nginx/security/whitelist.conf;\n";

        // Include PHP-FPM pool configuration if PHP is selected.
        if ($vhost->php_version !== 'none') {
            $pool = PhpFpmPool::where('version', $vhost->php_version)->first();
            if ($pool) {
                $content .= "    include /srv/{$vhost->server_name}/config/nginx/php/php_ngx_{$pool->version}_{$pool->id}.conf;\n";
            }
        }

        $content .= "    include /srv/{$vhost->server_name}/config/nginx/live/*;\n";
        $content .= "    include /nginx/config/live_global/*.conf;\n\n";
        $content .= "    access_log /srv/{$vhost->server_name}/logs/access.log {$vhost->log_type};\n";
        $content .= "    error_log /srv/{$vhost->server_name}/logs/error.log;\n";
        $content .= "}\n";

        $filePath = $this->directory . '/' . $vhost->file . '.conf';

        if (file_put_contents($filePath, $content) === false) {
            throw new \Exception("Failed to write config file to {$filePath}");
        }

        return true;
    }

    // Create the necessary domain home directory
    private function create_home($domain)
    {
        $baseDir = '/srv/' . $domain;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true)) {
            throw new \Exception("Failed to create directory: {$baseDir}");
        }

        $defaultDir = '/srv/default';
        $this->recursiveCopy($defaultDir, $baseDir);
    }

    // Recursively copy default content
    private function recursiveCopy($src, $dst)
    {
        if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
            throw new \Exception("Failed to create directory: {$dst}");
        }

        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') continue;

            $srcPath = rtrim($src, '/') . '/' . $file;
            $dstPath = rtrim($dst, '/') . '/' . $file;

            if (is_dir($srcPath)) {
                $this->recursiveCopy($srcPath, $dstPath);
            } else {
                if (!copy($srcPath, $dstPath)) {
                    throw new \Exception("Failed to copy file: {$srcPath} to {$dstPath}");
                }
            }
        }
    }

    // Reload Nginx configuration
    private function reloadNginx()
    {
        exec('/usr/sbin/nginx -t', $testOutput, $testReturn);
        if ($testReturn !== 0) {
            return [
                'status'  => 'error',
                'message' => "Nginx test failed:<br>" . implode("\n", $testOutput)
            ];
        }

        exec('/usr/sbin/nginx -s reload', $reloadOutput, $reloadReturn);
        if ($reloadReturn !== 0) {
            return [
                'status'  => 'error',
                'message' => "Error reloading Nginx:<br>" . implode("\n", $reloadOutput)
            ];
        }

        return [
            'status'  => 'success',
            'message' => 'Configuration updated and Nginx reloaded successfully!'
        ];
    }

    public function show($id)
    {
        $vhost = Vhost::findOrFail($id);
        $nginxConfigPath = $this->directory . '/' . $vhost->file . '.conf';
        $nginxConfig = file_exists($nginxConfigPath) ? file_get_contents($nginxConfigPath) : '';

        return response()->json([
            'vhost' => $vhost,
            'nginxConfig' => $nginxConfig
        ]);
    }
}
