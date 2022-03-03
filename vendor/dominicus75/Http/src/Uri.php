<?php

namespace Dominicus75\Http;

class Uri
{

    /**
     * Az URI sémával tér vissza
     * 
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#31-s%C3%A9ma-scheme1
     * @return string Az URI séma komponense.
     */
    public static function getScheme(): string {
        return preg_match("/^(http|https)$/i", $_SERVER['REQUEST_SCHEME'])
                ? strtolower($_SERVER['REQUEST_SCHEME'])
                : strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]);
    }

    /**
     * Lekéri az URI gazdagép (Host) komponensét.
     *
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#322-gazdag%C3%A9p-host
     * @return string Az URI gazdagép komponense.
     */
    public static function getHost(): string {
        return ($_SERVER['HTTP_HOST'] == $_SERVER['SERVER_NAME']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ;
    }

    /**
     * Lekéri az URI port komponensét.
     *
     * @return null|int Az URI port komponense.
     */
    public static function getPort(): int {
        return preg_match("/^(80|443)$/", $_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
    }

    /**
     * Lekéri az URI útvonal (path) komponensét.
     *
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#2-karakterk%C3%A9szlet
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#33-%C3%BAtvonal-path
     * @return string Az URI útvonal komponense.
     */
    public static function getPath(): string {

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return  preg_match("/^\/([\/a-zA-Z0-9_\-\.~]{1,255})?$/i", $path) ? $path : '/';

    }

    /**
     * Lekéri az URI lekérdezési (query string) komponensét.
     *
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#2-karakterk%C3%A9szlet
     * @see https://github.com/dominicus75/fig-standards/blob/master/related-rfcs/3986.md#34-lek%C3%A9rdez%C3%A9s-query
     * @return string Az URI lekérdezési komponense.
     */
    public static function getQuery(): string {

        if(isset($_SERVER['QUERY_STRING'])) {
            return preg_match("/^([a-zA-Z0-9_\-\&\=\.\/]{10,128})$/i", $_SERVER['QUERY_STRING'])
                ? $_SERVER['QUERY_STRING'] : '';
        } else { return ''; }

    }

}