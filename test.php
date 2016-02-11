<?php
header('Content-Type: text/html; charset=utf-8');

/*$json  = '{"shop_settings": {"one_click_order": true, "one_click_order_on_portal": true, "contact_now_display_off": true}, "name": "\u0410\u041d \u041c\u0410\u0419\u0414\u0410\u041d", "phones": [{"number": "+380 (97) 039-19-67", "description": ""}], "contacts_url": "http://an-majdan.prom.ua/contacts", "absolute_url": "http://an-majdan.prom.ua/", "invalid_phone_url": "http://prom.ua/company/mark_invalid_phone/2201072?page_place=portal-catalog-companies", "id": 2201072}';
echo '<pre>';
var_dump(json_decode($json));die;
echo '</pre>';*/

$file = __DIR__ . '/content/cc12-Transport.html';
$h = fopen($file, 'r');
$content = fread($h, filesize($file));
fclose($h);

//[]*<a[\s]+href\="(.*?)"[\s]*>(.*?)<\/a>[.]*;
//$pattern = '/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/';

//$pattern = '/<li[\s]+class\="b\-category-list__item">(.*?)<\/li>/is';
//
//preg_match_all($pattern, $content, $list, PREG_SET_ORDER);
//$pattern = '/<a[\s]+href\="(.*?)">(.*?)<\/a>/is';
//foreach ($list as $li) {
//    preg_match_all($pattern, $li[1], $link, PREG_SET_ORDER);
//
//    echo '<pre>';
//    var_dump($link);
//    echo '</pre>';
//
//    die;
//}

//$pattern = '/<a[\s.]*class\=".*pager_lastitem"[\s.]*href\="(.*?)">.*<\/a>/is';
$pattern = '/<a class=".*pager_lastitem" href="(.*?)"[^>]*>.*<\/a>/is';
preg_match_all($pattern, $content, $href, PREG_SET_ORDER);
$pattern = '/\?.*/';
$href = preg_replace($pattern, '', $href[0][1]);
var_dump($href);

