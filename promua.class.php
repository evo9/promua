<?php

class Promua
{
    const COMPANY_CATEGORIES = 'companies';

    private $db;

    private $snoopy;

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
        $content = '';
        if (file_exists($file)) {
            $h = fopen($file, 'r');
            $content = fread($h, filesize($file));
            fclose($h);
        }

        return $content;
    }

    private function nextLink($file)
    {
        $content = $this->getContent($file);

        $pattern = '/<a class=".*pager_lastitem" href="(.*?)"[^>]*>.*<\/a>/is';
        preg_match_all($pattern, $content, $next, PREG_SET_ORDER);

        if (count($next) > 0 && isset($next[0][1])) {
            $pattern = '/\?.*/';
            $next = preg_replace($pattern, '', $next[0][1]);
            return $this->removeSlash($next);
        }

        return null;
    }

    private function removeSlash($str)
    {
        $pattern = '/\//';

        return preg_replace($pattern, '', $str);
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
        $content = $this->getContent($file);

        $pattern = '/<li[\s]+class\="b\-category-list__item">(.*?)<\/li>/is';
        preg_match_all($pattern, $content, $list, PREG_SET_ORDER);
        $categories = [];
        $pattern = '/<a[\s]+href\="(.*?)">(.*?)<\/a>/is';
        foreach ($list as $li) {
            preg_match_all($pattern, $li[1], $link, PREG_SET_ORDER);
            if (count($link) > 0 && isset($link[0][1])) {
                $categories[] = [
                    'href' => $this->removeSlash($link[0][1]),
                    'title' => $link[0][2]
                ];
            }
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

        $categories = $this->db->getAll('SELECT * FROM `company_categories`');
        foreach ($categories as $cat) {
            $this->paginate($cat['href'], $referer, $cat['id']);

            $sql = 'UPDATE `company_categories` SET is_read = 1 WHERE id = ?i';
            $this->db->query($sql, $cat['id']);

            echo 'Пройдена вся категория';
        }
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

        echo 'Добавлена страница';

        $nextLink = self::nextLink($file);
        if ($nextLink) {
            sleep(rand(10, 60));
            $this->paginate($nextLink, $link, $categoryId);
        }
        else {
            return;
        }
    }

    public function testSql()
    {
        $sql = 'UPDATE `categories` SET is_read = 1 WHERE category_id = ?i';
        $this->db->query($sql, 12);

    }
}