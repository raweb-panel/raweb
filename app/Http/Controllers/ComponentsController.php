<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ComponentsController extends Controller
{
    protected $componentsPath = 'app/Helpers/Components';

    public function index()
    {
        $components = [];
        $files = File::files(base_path($this->componentsPath));

        foreach ($files as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if ($data) {
                $components[] = $data;
            }
        }

        return response()->json(['components' => $components]);
    }

    public function install(Request $request)
    {
        $name = $request->input('name');
        $filePath = base_path("{$this->componentsPath}/{$name}.json");

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Component not found'], 404);
        }

        $component = json_decode(File::get($filePath), true);

        if ($component['installed']) {
            return response()->json(['message' => 'Component already installed'], 400);
        }

        // Add repository if declared
        if (!empty($component['repo']['enabled']) && $component['repo']['enabled']) {
            $process = new Process(['add-apt-repository', '-y', $component['repo']['url']]);
            $process->run();
        }

        // Install packages
        $packages = $component['packages'] ?? [];
        if (!empty($packages)) {
            $process = new Process(array_merge(['apt-get', 'install', '-y'], $packages));
            $process->run();
        }

        // Update JSON file to mark as installed
        $component['installed'] = true;
        File::put($filePath, json_encode($component, JSON_PRETTY_PRINT));

        return response()->json(['message' => 'Component installed successfully']);
    }

    public function uninstall(Request $request)
    {
        $name = $request->input('name');

        // Search through components to find the matching file based on the 'name' property
        $files = File::files(base_path($this->componentsPath));
        $filePath = null;
        $component = null;
        foreach ($files as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if ($data && isset($data['name']) && $data['name'] === $name) {
                $filePath = $file->getPathname();
                $component = $data;
                break;
            }
        }

        if (!$filePath || !$component) {
            return response()->json(['error' => 'Component not found'], 404);
        }

        if (!$component['installed']) {
            return response()->json(['message' => 'Component is not installed'], 400);
        }

        // Uninstall packages
        $packages = $component['packages'] ?? [];
        if (!empty($packages)) {
            $process = new Process(array_merge(['apt-get', 'remove', '-y'], $packages));
            $process->run();
        }

        // Update JSON file to mark as not installed
        $component['installed'] = false;
        File::put($filePath, json_encode($component, JSON_PRETTY_PRINT));

        return response()->json(['message' => 'Component uninstalled successfully']);
    }

    public function streamInstallLogs($name)
    {
        $name = urldecode($name);
    
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ini_set('zlib.output_compression', 0);
        ob_implicit_flush(true);
        set_time_limit(0);
    
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    
        // Locate the component file
        $files = File::files(base_path($this->componentsPath));
        $filePath = null;
        $component = null;
        foreach ($files as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if ($data && isset($data['name']) && $data['name'] === $name) {
                $filePath = $file->getPathname();
                $component = $data;
                break;
            }
        }
    
        if (!$filePath || !$component) {
            echo "data: Component not found\n\n";
            echo "event: close\n\n";
            flush();
            return;
        }
    
        if ($component['installed']) {
            echo "data: Component already installed\n\n";
            echo "event: close\n\n";
            flush();
            return;
        }
    
        // Optionally add repository if declared
        if (!empty($component['repo']['enabled']) && $component['repo']['enabled']) {
            echo "data: Adding repository...\n\n";
            flush();
    
            $repoCommand = ['stdbuf', '-o0', 'add-apt-repository', '-y', $component['repo']['url']];
            $process = new Process($repoCommand);
            $process->run(function ($type, $buffer) {
                echo "data: " . trim($buffer) . "\n\n";
                flush();
            });
        }
    
        // Install packages
        $packages = $component['packages'] ?? [];
        if (!empty($packages)) {
            echo "data: Installing packages...\n\n";
            flush();
    
            $installCommand = array_merge(
                ['stdbuf', '-o0', 'apt-get', 'install', '-y', '-o', 'Debug::NoProgressBar=1'],
                $packages
            );
            $process = new Process($installCommand);
            $process->start();
    
            while ($process->isRunning()) {
                $output = $process->getIncrementalOutput();
                $error  = $process->getIncrementalErrorOutput();
                if (!empty($output)) {
                    echo "data: " . trim($output) . "\n\n";
                    flush();
                }
                if (!empty($error)) {
                    echo "data: " . trim($error) . "\n\n";
                    flush();
                }
                usleep(200000);
            }
            $remaining = $process->getIncrementalOutput();
            if (!empty($remaining)) {
                echo "data: " . trim($remaining) . "\n\n";
                flush();
            }
        }
    
        // Mark component as installed
        $component['installed'] = true;
        File::put($filePath, json_encode($component, JSON_PRETTY_PRINT));
    
        echo "data: Installation script completed.\n\n";
        echo "event: close\n\n";
        flush();
    }
    
    public function streamUninstallLogs($name)
    {
        $name = urldecode($name);
    
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ini_set('zlib.output_compression', 0);
        ob_implicit_flush(true);
        set_time_limit(0);
    
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    
        // Locate the component file
        $files = File::files(base_path($this->componentsPath));
        $filePath = null;
        $component = null;
        foreach ($files as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if ($data && isset($data['name']) && $data['name'] === $name) {
                $filePath = $file->getPathname();
                $component = $data;
                break;
            }
        }
    
        if (!$filePath || !$component) {
            echo "data: Component not found\n\n";
            echo "event: close\n\n";
            flush();
            return;
        }
    
        if (!$component['installed']) {
            echo "data: Component is not installed\n\n";
            echo "event: close\n\n";
            flush();
            return;
        }
    
        echo "data: Uninstalling packages...\n\n";
        flush();
    
        $packages = $component['packages'] ?? [];
        if (!empty($packages)) {
            $uninstallCommand = array_merge(
                ['stdbuf', '-o0', 'apt-get', 'remove', '-y', '-o', 'Debug::NoProgressBar=1'],
                $packages
            );
            $process = new Process($uninstallCommand);
            $process->start();
    
            while ($process->isRunning()) {
                $output = $process->getIncrementalOutput();
                $error  = $process->getIncrementalErrorOutput();
                if (!empty($output)) {
                    echo "data: " . trim($output) . "\n\n";
                    flush();
                }
                if (!empty($error)) {
                    echo "data: " . trim($error) . "\n\n";
                    flush();
                }
                usleep(200000);
            }
            $remaining = $process->getIncrementalOutput();
            if (!empty($remaining)) {
                echo "data: " . trim($remaining) . "\n\n";
                flush();
            }
        }
    
        // Mark component as uninstalled
        $component['installed'] = false;
        File::put($filePath, json_encode($component, JSON_PRETTY_PRINT));
    
        echo "data: Uninstallation completed.\n\n";
        echo "event: close\n\n";
        flush();
    }
}