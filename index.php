<?php

define('ROOT', __DIR__);

require_once(ROOT . '/config.php');
require_once(ROOT . '/safemysql.class.php');

$path = '';

$content = getCurlContent($path);

$h = fopen(CSV . 'companies', 'w+');
fwrite($h, $content);
fclose($h);

function getCurlContent($path, $urlData = null)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, DOMAIN . $path);
    curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
    $headers = array
    (
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
        'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
        'Accept-Encoding: deflate',
        'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7'
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if (!$urlData) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $urlData);
    }
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}