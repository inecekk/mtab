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
    $chars = md5(uniqid(mt_rand(), true));
    return substr($chars, 0, 8) . '-'
        . substr($chars, 8, 4) . '-'
        . substr($chars, 12, 4) . '-'
        . substr($chars, 16, 4) . '-'
        . substr($chars, 20, 12);
}

function renderToken($t = 'tab'): string
{
    $s = uuid() . strval(time()) . $t;
    return md5($s);
}

function joinPath($path1, $path2='')
{
    return preg_replace("#/+/#", "/", $path1 . $path2);
}

function getRealIp(): string
{
    $ip1 = request()->header('x-forwarded-for', false);
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