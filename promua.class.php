<?php

class Promua
{
    const COMPANY_CATEGORIES = 'company-categories';

    private $db;

    private $snoopy;

    private $slash = '/\/+/';

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

        if (file_exists($file)) {
            $h = fopen($file, 'r');
            $content = fread($h, filesize($file));
            fclose($h);

            $pattern = '/<li[\s]+class\="b\-category-list__item">(.*?)<\/li>/is';
            preg_match_all($pattern, $content, $list, PREG_SET_ORDER);

            $links = [];
            $pattern = '/<a[\s]+href\="(.*?)">(.*?)<\/a>/is';
            foreach ($list as $li) {
                preg_match_all($pattern, $li[1], $link, PREG_SET_ORDER);
                $links[] = [
                    'href' => $link[0][1],
                    'title' => $link[0][2]
                ];
            }

            $this->saveCompanyCategories($links);
        }
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

    public function getCompanies()
    {
        $referer = DOMAIN . self::COMPANY_CATEGORIES;

        $categories = $this->db->getAll('SELECT * FROM `company_categories`');
        foreach ($categories as $cat) {
            $this->snoopy->referer = $referer;
            $href = $this->removeSlash($cat['href']);

            $path = DOMAIN . $href;
            $this->snoopy->fetch($path);
            $content = $this->snoopy->results;

            $file = CONTENT . $href;
            $this->makeFile($content, $file);

            die;
        }
    }

    private function removeSlash($str)
    {
        $pattern = '/\//';

        return preg_replace($pattern, '', $str);
    }
}