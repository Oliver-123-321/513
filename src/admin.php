<?php
declare(strict_types=1);

/**
 * Admin authentication and JSON file management functions
 */

function isAdminLoggedIn(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['admin']['logged_in']) && $_SESSION['admin']['logged_in'] === true;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}

function getJsonFiles(): array
{
    $dataDir = __DIR__ . '/../data';
    $files = [];
    
    if (is_dir($dataDir)) {
        $items = scandir($dataDir);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && pathinfo($item, PATHINFO_EXTENSION) === 'json') {
                $files[] = $item;
            }
        }
    }
    
    return $files;
}

function readJsonFile(string $filename): array
{
    $filepath = __DIR__ . '/../data/' . basename($filename);
    
    if (!file_exists($filepath)) {
        throw new Exception("File not found: {$filename}");
    }
    
    if (!is_readable($filepath)) {
        throw new Exception("File is not readable: {$filename}");
    }
    
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format: " . json_last_error_msg());
    }
    
    return $data;
}

function writeJsonFile(string $filename, array $data): bool
{
    $filepath = __DIR__ . '/../data/' . basename($filename);
    $dataDir = dirname($filepath);
    
    // Ensure data directory exists
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    // Validate JSON before writing
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($json === false) {
        throw new Exception("Failed to encode JSON: " . json_last_error_msg());
    }
    
    // Verify the JSON is valid by decoding it
    $decoded = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Generated invalid JSON: " . json_last_error_msg());
    }
    
    $result = file_put_contents($filepath, $json, LOCK_EX);
    
    if ($result === false) {
        throw new Exception("Failed to write file: {$filename}");
    }
    
    return true;
}

function validateJsonString(string $jsonString): array
{
    $data = json_decode($jsonString, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format: " . json_last_error_msg());
    }
    
    if (!is_array($data)) {
        throw new Exception("JSON must be an object or array");
    }
    
    return $data;
}

function deleteJsonFile(string $filename): bool
{
    $filepath = __DIR__ . '/../data/' . basename($filename);
    
    if (!file_exists($filepath)) {
        throw new Exception("File not found: {$filename}");
    }
    
    if (!is_writable($filepath)) {
        throw new Exception("File is not writable: {$filename}");
    }
    
    return unlink($filepath);
}

/**
 * Product helpers (products.json)
 */
function loadProductsData(): array
{
    $filepath = __DIR__ . '/../data/products.json';
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    return $data['products'] ?? [];
}

function saveProductsData(array $products): void
{
    $filepath = __DIR__ . '/../data/products.json';
    $payload = ['products' => array_values($products)];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new Exception('Failed to encode products JSON: ' . json_last_error_msg());
    }
    file_put_contents($filepath, $json, LOCK_EX);
}

