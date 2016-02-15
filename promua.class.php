<?php

class Promua
{
    const COMPANY_CATEGORIES = 'companies';

    private $db;

    private $snoopy;

    private $company;

    private $companies;

    public function __construct($dbOpt = [])
    {
        $this->db = new SafeMySQL($dbOpt);
        $this->snoopy = new Snoopy();
        $this->snoopy->agent = USER_AGENT;
    }

    public function makeFile($content, $file)
    {
        $h = fopen($file, 'w+');
        fwrite($h, $content);
        fclose($h);
    }

    private function getContent($file)
    {
        return file_get_contents($file);
    }

    private function nextLink($file)
    {
        $html = file_get_html($file);
        $link = $html->find('a.pager_lastitem');
        if ($link) {
            $pattern = '/\?.*/';
            $next = preg_replace($pattern, '', $link[0]->href);
            return $this->removeSlash($next);
        }

        return null;
    }

    private function removeSlash($str)
    {
        $pattern = '/\//';

        return preg_replace($pattern, '', $str);
    }

    private function getAttrData($element, $attr, $isJson = true)
    {
        $json = htmlspecialchars_decode($element->__get($attr));
        if ($isJson) {
            return json_decode($json);
        }
        return $json;
    }

    public function getCompanyCategories()
    {
        $page = self::COMPANY_CATEGORIES;
        $path = DOMAIN . $page;
        $this->snoopy->referer = DOMAIN;

        $this->snoopy->fetch($path);
        $content = $this->snoopy->results;

        if (!is_dir(CONTENT)) {
            mkdir(CONTENT, 0777);
        }
        $file = CONTENT . $page . CONTENT_EXT;
        $this->makeFile($content, $file);
    }

    public function parseCompanyCategories()
    {
        $file = CONTENT . self::COMPANY_CATEGORIES . CONTENT_EXT;
        $html = file_get_html($file);
        $links = $html->find('li.b-category-list__item > a');
        $categories = [];
        foreach ($links as $link) {
            $categories[] = [
                'href' => $this->removeSlash($link->href),
                'title' => $link->innertext
            ];
        }

        $this->saveCompanyCategories($categories);
    }

    public function saveCompanyCategories($categories)
    {
        $sql = 'INSERT INTO `company_categories` (`category`, `href`) VALUES ';

        $values = [];
        foreach ($categories as $cat) {
            $values[] = '(\'' . addslashes($cat['title']) . '\', \'' . addslashes($cat['href']) . '\')';
        }

        $sql .= implode(', ', $values);

        $this->db->query($sql);
    }

    public function getCompaniesListContent()
    {
        $referer = DOMAIN . self::COMPANY_CATEGORIES;

        $lastRecord = $this->db->getAll('SELECT id, category_id, href FROM `categories` ORDER BY id DESC LIMIT 1');

        $sql = 'SELECT * FROM `company_categories` WHERE is_read = ?i';
        $categories = $this->db->getAll($sql, 0);
        foreach ($categories as $cat) {
            if ($lastRecord) {
                $lastRecord = $lastRecord[0];
                $this->db->query('DELETE FROM `categories` WHERE id = ?i', $lastRecord['id']);
                if ($cat['id'] < $lastRecord['category_id']) {
                    continue;
                }
                else {
                    $this->paginate($lastRecord['href'], $referer, $lastRecord['category_id']);
                }
                $lastRecord = null;
            }
            else {
                $this->paginate($cat['href'], $referer, $cat['id']);
            }

            $sql = 'UPDATE `company_categories` SET is_read = 1 WHERE id = ?i';
            $this->db->query($sql, $cat['id']);

            echo "Пройдена вся категория \r\n";
        }

        echo "Конец процесса \r\n";
    }

    private function paginate($link, $referer, $categoryId)
    {
        $path = DOMAIN . $link;
        $this->snoopy->referer = $referer;
        $this->snoopy->fetch($path);
        $content = $this->snoopy->results;
        $file = CONTENT . $link;
        $this->makeFile($content, $file);

        $sql = 'INSERT INTO `categories` SET category_id = ?i, href = ?s';
        $this->db->query($sql, $categoryId, $link);

        echo "Добавлена страница " . $link . " \r\n";

        $nextLink = self::nextLink($file);
        if ($nextLink) {
            sleep(rand(10, 60));
            $this->paginate($nextLink, $link, $categoryId);
        }
        else {
            return;
        }
    }

    public function getCompanyInfo()
    {
        $this->db->query('TRUNCATE `companies`');

        $sql = 'SELECT * FROM `categories` WHERE is_read = ?i';
        $companies = $this->db->getAll($sql, 0);
        foreach ($companies as $c) {
            $file = CONTENT . $c['href'];
            $html = file_get_html($file);
            $items = $html->find('.b-product-line__item');
            foreach ($items as $item) {
                $this->setCompany($c['category_id']);
                $this->getTitle($item);
                $sidebar = $this->getSidebar($item);
                if ($sidebar) {
                    $this->getPhones($sidebar);
                    $this->getCity($sidebar);
                    $this->getReviews($sidebar);
                }

                $this->saveCompany();
                $sql = 'UPDATE `categories` SET is_read = 1 WHERE id = ?i';
                $this->db->query($sql, $c['id']);
                echo "Добавлена компания " . $this->company['title'] . " \r\n";
            }
            echo "Страница пройдена \r\n";
        }
    }

    private function setCompany($categoryId)
    {
        $this->company = [
            'category_id' => $categoryId,
            'title' => null,
            'site' => null,
            'main_phone' => null,
            'phones' => null,
            'other_contacts' => null,
            'contact_page' => null,
            'city' => null,
            'reviews' => null,
            'other' => null
        ];
    }

    private function saveCompany()
    {

        $fields = [
            'category_id',
            'title',
            'site',
            'main_phone',
            'phones',
            'other_contacts',
            'contact_page',
            'city',
            'reviews',
            'other'
        ];
        $data = [];
        foreach ($fields as $key => $field) {
            if (isset($this->company[$field])) {
                $data[$field] = htmlentities($this->company[$field]);
            }
        }
        $sql = 'INSERT INTO `companies` SET ?u';

        $this->db->query($sql, $data);
    }

    private function getTitle($item)
    {
        $title = $item->find('h3 > a');
        if ($title) {
            $this->company['title'] = trim($title[0]->innertext);
            $this->company['site'] = $title[0]->href;
        }
    }

    private function getSidebar($item)
    {
        $sidebar = $item->find('.h-width-240');
        if ($sidebar) {
            return $sidebar[0];
        }
        return null;
    }

    private function getPhones($sidebar)
    {
        $phonesBlock = $sidebar->find('div.b-arrow-box > div.b-iconed-text > div.b-iconed-text__text-holder > span.b-pseudo-link > span');
        if ($phonesBlock) {
            $this->company['main_phone'] = $this->getAttrData($phonesBlock[0], 'data-pl-main-phone', false);
            $this->company['contact_page'] = $this->getAttrData($phonesBlock[0], 'data-pl-contacts-url', false);
            $phones = $this->getAttrData($phonesBlock[0], 'data-pl-phones');
            if ($phones) {
                $tmp = [];
                foreach ($phones->data as $phone) {
                    $tmp[] = [
                        'description' => $phone->description,
                        'number' => $phone->number
                    ];
                }
                $this->company['phones'] = serialize($tmp);
            }
            $contacts = $this->getAttrData($phonesBlock[0], 'data-pl-extra-contacts');
            if ($contacts) {
                $tmp = [];
                foreach ($contacts->data as $contact) {
                    $tmp[] = [
                        'description' => $contact->description,
                        'data' => $contact->data
                    ];
                }
                $this->company['other_contacts'] = serialize($tmp);
            }
        }
    }

    private function getCity($sidebar)
    {
        $cityBlock = $sidebar->find('div.b-text-hider_type_multi-line > div.b-iconed-text');
        if ($cityBlock) {
            $this->company['city'] = $this->getAttrData($cityBlock[0], 'title', false);
        }
    }

    private function getReviews($sidebar)
    {
        $reviewsBlock = $sidebar->find('div.b-text-hider_type_multi-line a.b-company-info__opinions-link ');
        if ($reviewsBlock) {
            $review = [
                'href' => $reviewsBlock[0]->href,
                'title' => $reviewsBlock[0]->innertext . ', '
            ];
            $parent = $reviewsBlock[0]->parent();
            $text = $parent->innertext;
            $exp = '/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)[\s,]*/is';
            $review['title'] .= preg_replace($exp, '', $text);

            $this->company['reviews'] = serialize($review);
        }
    }

    public function generateCsv()
    {
        $companies = $this->db->getAll('SELECT DISTINCT * FROM `companies` ');
        echo "Получил компании \r\n";
        $companyCategories = $this->db->getAll('SELECT c.title, cc.category FROM `companies` c JOIN `company_categories` cc ON (cc.id = c.category_id)');
        echo "Получил категории компаний \r\n";
        $csvArr = [];
        $csvArr[] = [
            'Категория',
            'Название компании',
            'Ссылка на сайт',
            'Основной телефон',
            'Телефоны',
            'Дополнительные контакты',
            'Ссылка на страницу контактов',
            'Город',
            'Отзывы'
        ];
        echo "Начало процесса формирования csv \r\n";
        foreach ($companies as $company) {
            echo $company['title'] . " \r\n";
            $company = $this->data($company);
            $categories = [];

            echo "Поиск категорий компании \r\n";
            foreach ($companyCategories as $key => $cc) {
                if ($company['title'] == html_entity_decode($cc['title'])) {
                    echo $cc['category'] . " \r\n";
                    $categories[] = $cc['category'];
                    unset($companyCategories[$key]);
                }
            }
            $categories = implode(', ', $categories);


            $phones = [];
            if($company['phones'] && is_array($company['phones'])) {
                foreach ($company['phones'] as $phone) {
                    $phones[] = $phone['description'] . ' - ' . $phone['number'];
                }
            }
            $phones = implode(', ', $phones);

            $contacts = [];
            if ($company['other_contacts'] && is_array($company['other_contacts'])) {
                foreach ($company['other_contacts'] as $contact) {
                    $contacts[] = $contact['description'] . ' - ' . $contact['data'];
                }
            }
            $contacts = implode(', ', $contacts);

            $reviews = $company['reviews']['title'] . '; Ссылка на отзывы - ' . $company['reviews']['href'];


            $csvArr[] = [
                $categories,
                $company['title'],
                $company['site'],
                $company['main_phone'],
                $phones,
                $contacts,
                $company['contact_page'],
                $company['city'],
                $reviews
            ];
        }

        echo "Запись csv файла \r\n";
        $file = date('d-m-Y') . '.csv';
        $path = CSV . $file;
        $csv = new CsvWriter($path, $csvArr);
        $csv->GetCsv();

        return $file;
    }

    private function data($record)
    {
        $result = [];

        foreach ($record as $field => $value) {
            $value = html_entity_decode($value);
            if (@unserialize($value) || is_array(@unserialize($value))) {
                $value = unserialize($value);
            }
            $result[$field] =  $value;
        }

        return $result;
    }

    private function getCategories()
    {
        $result = [];
        $categories = $this->db->getAll('SELECT * FROM `company_categories`');
        foreach ($categories as $category) {
            $result[$category['id']] = $category['category'];
        }
        return $result;
    }

    public function testSql()
    {
        $sql = 'UPDATE `categories` SET is_read = 1 WHERE category_id = ?i';
        $this->db->query($sql, 12);

    }

    public function testHtml($template, $element)
    {
        $file = CONTENT . $template;
        $html = file_get_html($file);
        $items = $html->find($element);

        foreach ($items as $item) {
            echo "Начинаю перебор компаний \r\n";
            $sidebar = $item->find('.h-width-240');
            if ($sidebar) {
                echo "Нашел sidebar \r\n";
                $phonesBlock = $sidebar[0]->find('div.b-arrow-box > div.b-iconed-text > div.b-iconed-text__text-holder > span.b-pseudo-link > span');
                if ($phonesBlock) {
                    echo "Нашел span с информацией о телефонах \r\n";
                    $mainPhone = $this->getAttrData($phonesBlock[0], 'data-pl-main-phone', false);
                    var_dump($mainPhone);
                    $phones = $this->getAttrData($phonesBlock[0], 'data-pl-phones');
                    //var_dump($phones->data[0]->number);
                }

            }
            die;
            //var_dump($title[0]->innertext);
        }

        die;
    }
}