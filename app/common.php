<?php
//程序版本号，请勿修改
const app_version = '2.4.3';
//程序内部更新版本代码，请勿修改
const app_version_code = 243;
// 应用公共文件
function validateEmail($email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    } else {
        return true;
    }
}

function uuid(): string
{
    try {
        $chars = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $chars = md5(uniqid('', true));
    }
    return substr($chars, 0, 8) . '-'
        . substr($chars, 8, 4) . '-'
        . substr($chars, 12, 4) . '-'
        . substr($chars, 16, 4) . '-'
        . substr($chars, 20, 12);
}

function renderToken($t = 'tab'): string
{
    try {
        return bin2hex(random_bytes(32));
    } catch (Exception $e) {
        return hash('sha256', uuid() . microtime(true) . $t);
    }
}

function hashUserPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyUserPassword(string $password, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }
    if (password_get_info($storedHash)['algo'] !== 0) {
        return password_verify($password, $storedHash);
    }
    return hash_equals($storedHash, md5($password));
}

function shouldRehashUserPassword(string $storedHash): bool
{
    if (password_get_info($storedHash)['algo'] === 0) {
        return true;
    }
    return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

function joinPath($path1, $path2='')
{
    return preg_replace("#/+/#", "/", $path1 . $path2);
}

function getRealIp(): string
{
    $trustProxy = function_exists('env') ? env('app.trust_proxy_headers', false) : false;
    $ip1 = $trustProxy ? request()->header('x-forwarded-for', false) : false;
    if ($ip1) {
        $arr = explode(",", $ip1);
        if (count($arr) > 0) {
            return trim($arr[0]);
        }
    }
    return request()->ip();
}

function plugins_path($path = ''): string
{
    if (mb_strlen($path) > 0) {
        if (strpos($path, "/") == 0) {
            return $_ENV['plugins_dir_name'] . $path;
        }
        return $_ENV['plugins_dir_name'] . '/' . $path;
    }
    return $_ENV['plugins_dir_name'] . "/";
}

function is_demo_mode($is_exit = false)
{
    // 禁用演示模式限制，允许所有操作
    return false;
}

function isSafeZipEntry(string $name): bool
{
    $name = str_replace('\\', '/', $name);
    if ($name === '' || strpos($name, "\0") !== false) {
        return false;
    }
    if ($name[0] === '/' || preg_match('/^[a-zA-Z]:\//', $name)) {
        return false;
    }
    foreach (explode('/', $name) as $part) {
        if ($part === '..') {
            return false;
        }
    }
    return true;
}

function safeExtractZip(\ZipArchive $zip, string $destination, array $blockedExtensions = []): bool
{
    $destination = rtrim($destination, "/\\") . DIRECTORY_SEPARATOR;
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!isSafeZipEntry($name)) {
            return false;
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($extension && in_array($extension, $blockedExtensions, true)) {
            return false;
        }
    }

    return $zip->extractTo($destination);
}

function modifyImageUrls($htmlContent, $newBaseUrl): string
{
    try {
        $dom = new DOMDocument();
        $htmlContent = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrappedContent = '<div>' . $htmlContent . '</div>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $oldSrc = $img->getAttribute('src');
            if (!preg_match('/^http/', $oldSrc)) {
                $newSrc = $newBaseUrl . $oldSrc;
                $img->setAttribute('src', $newSrc);
            }
        }

        // 返回修改后的 HTML，去掉根节点
        $newHtmlContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $newHtmlContent .= $dom->saveHTML($child);
        }
        return $newHtmlContent;
    } catch (Exception $e) {
        return $htmlContent;
    }
}

function removeImagesUrls($htmlContent, $newBaseUrl)
{
    try {
        $dom = new DOMDocument();
        $htmlContent = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrappedContent = '<div>' . $htmlContent . '</div>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $domain = $newBaseUrl;
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $oldSrc = $img->getAttribute('src');
            $newSrc = str_replace($domain, '', $oldSrc);
            $img->setAttribute('src', $newSrc);
        }

        // 返回修改后的 HTML，去掉根节点
        $newHtmlContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $newHtmlContent .= $dom->saveHTML($child);
        }
        return $newHtmlContent;
    } catch (Exception $e) {
        return $htmlContent;
    }
}
