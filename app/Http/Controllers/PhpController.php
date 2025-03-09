<?php

namespace App\Http\Controllers;

use App\Models\PhpFpmPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PhpController extends Controller
{
    /**
     * Display a listing of the PHP pools.
     */
    public function index()
    {
        $pools = PhpFpmPool::orderBy('version')->orderBy('name')->get();
        return view('php.index', compact('pools'));
    }

    public function api_index()
    {
        $PhpFpmPools = PhpFpmPool::all();
        return response()->json($PhpFpmPools);
    }

    public function check_port_aviability(Request $request)
    {
        $listen = $request->input('listen');
        $exists = PhpFpmPool::where('listen', $listen)->exists();
        return response()->json(['exists' => $exists]);
    }

    /**
     * Store a newly created PHP pool in storage.
     * Also creates a dedicated PHP-FPM pool config file.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'                => 'required|max:191|min:1|unique:php_fpm_pools,name',
            'version'             => 'required|string|max:10', 
            'type'                => 'required|in:ip,sock',
            'listen'              => 'required|string', // e.g., for ip: 127.0.0.1:9000 or for sock: /var/run/php/pool.sock
            'user'                => 'required|string|max:191',
            'pm_max_children'     => 'required|integer|min:1',
            'pm_max_requests'     => 'required|integer|min:1',
            'ram_limit'           => 'required|string',  // e.g., "128M"
            'max_vars'            => 'required|integer|min:1',
            'max_execution_time'  => 'required|integer|min:1',
            'max_upload'          => 'required|string', // e.g., "50M"
            'display_errors'      => 'required|boolean',
        ]);

        try {
            // Create database record
            $pool = PhpFpmPool::create($validatedData);

            // Create the dedicated pool file
            $this->createFpmPoolFile($pool);

            // Create the Nginx config file
            $this->createNginxConfigFile($pool);

            return response()->json(['message' => 'PHP pool created successfully.', 'pool' => $pool], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create PHP pool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified PHP pool in storage and update its fpm config file.
     */
    public function update(Request $request, PhpFpmPool $phpFpmPool)
    {
        $validatedData = $request->validate([
            'name'                => 'required|max:191|min:1|unique:php_fpm_pools,name,' . $phpFpmPool->id,
            'version'             => 'required|string|max:10', 
            'type'                => 'required|in:ip,sock',
            'listen'              => 'required|string',
            'user'                => 'required|string|max:191',
            'pm_max_children'     => 'required|integer|min:1',
            'pm_max_requests'     => 'required|integer|min:1',
            'ram_limit'           => 'required|string',
            'max_vars'            => 'required|integer|min:1',
            'max_execution_time'  => 'required|integer|min:1',
            'max_upload'          => 'required|string',
            'display_errors'      => 'required|boolean',
        ]);

        try {
            $phpFpmPool->update($validatedData);
            
            // Regenerate the FPM pool file
            $this->createFpmPoolFile($phpFpmPool);

            // Create the Nginx config file
            $this->createNginxConfigFile($phpFpmPool);

            return response()->json(['message' => 'PHP pool updated successfully.', 'pool' => $phpFpmPool], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update PHP pool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified PHP pool and its config file.
     */
    public function destroy($id)
    {
        try {
            // Fetch the PHP-FPM pool explicitly
            $phpFpmPool = PhpFpmPool::findOrFail($id);

            // Get the config file paths
            $fpmFile = $this->getFpmFilePath($phpFpmPool);
            $nginxConfigPath = "/srv/default/config/nginx/php/php_ngx_{$phpFpmPool->version}_{$phpFpmPool->id}.conf";

            // Remove the PHP-FPM config file if it exists
            if (file_exists($fpmFile)) {
                unlink($fpmFile);
            }

            // Remove the Nginx config file if it exists
            if (file_exists($nginxConfigPath)) {
                unlink($nginxConfigPath);
            }

            // Remove the database entry
            $phpFpmPool->delete();

            return response()->json(['message' => 'PHP pool deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete pool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create or update a dedicated PHP-FPM pool file.
     */
    private function createFpmPoolFile(PhpFpmPool $pool)
    {
        // Base pool file to copy from, e.g., the default pool config
        $sourceFile = "/etc/php/{$pool->version}/fpm/pool.d/www.conf";
        // Destination file for the dedicated pool
        $destFile = $this->getFpmFilePath($pool);

        if (!file_exists($sourceFile)) {
            throw new \Exception("Source pool config does not exist: {$sourceFile}");
        }

        // Copy the source file to destination
        if (!copy($sourceFile, $destFile)) {
            throw new \Exception("Failed to copy {$sourceFile} to {$destFile}");
        }

        // Read the newly copied file
        $config = file_get_contents($destFile);

        // Replace placeholders with new values, including the pool name in the first square brackets.
        $replacements = [
            // Replace the pool name in the header line (e.g., [www]) with the user-submitted pool name.
            '/^\[[^\]]+\]/m' => "[{$pool->name}]",
            '/^user\s*=.*$/m'              => "user = {$pool->user}",
            '/^group\s*=.*$/m'             => "group = {$pool->user}",
            '/^listen\s*=.*$/m'            => "listen = " . ($pool->type == 'ip' ? $pool->listen : "unix:" . $pool->listen),
            '/^listen\.owner\s*=.*$/m'      => "listen.owner = {$pool->user}",
            '/^listen\.group\s*=.*$/m'      => "listen.group = {$pool->user}",
            '/^pm\.max_children\s*=.*$/m'   => "pm.max_children = {$pool->pm_max_children}",
            '/^pm\.max_requests\s*=.*$/m'   => "pm.max_requests = {$pool->pm_max_requests}",
            '/^php_admin_value\s*\[memory_limit\]\s*=.*$/m' => "php_admin_value[memory_limit] = {$pool->ram_limit}",
            '/^php_admin_value\s*\[max_input_vars\]\s*=.*$/m' => "php_admin_value[max_input_vars] = {$pool->max_vars}",
            '/^php_admin_value\s*\[max_execution_time\]\s*=.*$/m' => "php_admin_value[max_execution_time] = {$pool->max_execution_time}",
            '/^php_admin_value\s*\[upload_max_filesize\]\s*=.*$/m' => "php_admin_value[upload_max_filesize] = {$pool->max_upload}",
            '/^php_admin_value\s*\[post_max_size\]\s*=.*$/m' => "php_admin_value[post_max_size] = {$pool->max_upload}",
            '/^php_flag\s*\[display_errors\]\s*=.*$/m' => "php_flag[display_errors] = " . ($pool->display_errors ? 'on' : 'off'),
        ];

        foreach ($replacements as $pattern => $replacement) {
            $config = preg_replace($pattern, $replacement, $config);
        }

        // Write changes back to the file
        if (file_put_contents($destFile, $config) === false) {
            throw new \Exception("Failed to write updated pool config to {$destFile}");
        }

        return true;
    }

    /**
     * Create or update a dedicated Nginx config file.
     */
    private function createNginxConfigFile(PhpFpmPool $pool)
    {
        $nginxConfigPath = "/srv/default/config/nginx/php/php_ngx_{$pool->version}_{$pool->id}.conf";
        $nginxConfigContent = <<<EOL
location ~ \.php {
  try_files \$uri \$uri /index.php =404;
  fastcgi_pass {$pool->listen};
  fastcgi_buffers 16 256k;
  fastcgi_buffer_size 256k;
  fastcgi_index index.php;
  fastcgi_read_timeout {$pool->max_execution_time};
  fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
  include fastcgi_params;
}
EOL;

        if (file_put_contents($nginxConfigPath, $nginxConfigContent) === false) {
            throw new \Exception("Failed to create Nginx config file at {$nginxConfigPath}");
        }
    }

    /**
     * Get the destination file path for the PHP-FPM pool config.
     */
    private function getFpmFilePath(PhpFpmPool $pool)
    {
        return "/etc/php/{$pool->version}/fpm/pool.d/{$pool->name}_{$pool->id}.conf";
    }
}
