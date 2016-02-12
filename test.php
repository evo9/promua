<?php
header('Content-Type: text/html; charset=utf-8');

$json  = '{"shop_settings": {"one_click_order": true, "one_click_order_on_portal": true, "contact_now_display_off": false}, "name": "\u0427\u041f \u0411\u0430\u0431\u0438\u0447", "phones": [{"number": "+380 (96) 953-95-44", "description": "\u0421\u0432\u0435\u0442\u043b\u0430\u043d\u0430"}, {"number": "+380 (66) 829-49-65", "description": "\u0410\u043d\u043d\u0430"}, {"number": "+380 (50) 956-20-75", "description": "\u0414\u043b\u044f \u0436\u0430\u043b\u043e\u0431 (\u0441\u043c\u0441)"}, {"number": "+380 (50) 298-69-47", "description": "\u0418\u043d\u043d\u0430"}], "contacts_url": "http://ranec.in.ua/contacts", "absolute_url": "http://ranec.in.ua/", "invalid_phone_url": "http://prom.ua/company/mark_invalid_phone/611137?page_place=portal-catalog-companies", "id": 611137}';
echo '<pre>';
var_dump(json_decode($json));
echo '</pre>';
die;

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

