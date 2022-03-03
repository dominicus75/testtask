<?php

namespace Dominicus75\Http;


class Request
{

    use MessageTrait;

    protected string $uri;
    protected array $query = [];
    protected array $post  = [];

    /**
     * A rendelkezésre álló szuperglobális tömbökből létrehozza az
     * üzenetet reprezentáló Request objektumot.
     * 
     * @return self
     */
    public function __construct() {

        $this->uri = Uri::getPath();

        $this->setHeaders(\apache_request_headers());

        if(count($_POST) > 0) {
            foreach(\filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS) as $key => $value) {
                $value = \urldecode($value);
                $this->post[$key] = $value;
            }
        }

        if(count($_GET) > 0) {
            foreach(\filter_input_array(INPUT_GET, FILTER_SANITIZE_URL) as $key => $value) {
                $this->query[$key] = $value;
            }
        }

    }

    /**
     * Ellenőrzi, hogy a kérés típusa XmlHttpRequest-e.
     * 
      * @return bool
     */
    public function isXmlHttpRequest(): bool {
        return array_key_exists("X-Requested-With", $this->headers);
    }

    /**
     * A kérésben használt HTTP metódust adja vissza
     * 
     * @return string 
     */
    public function getMethod(): string {
        return preg_match("/^(get|post)$/i", $_SERVER['REQUEST_METHOD'])
                    ? strtoupper($_SERVER['REQUEST_METHOD'])
                    : 'GET' ;
    }

    /**
     * Szövegként adja vissza az URI útvonal komponensét
     * 
     * @return string
     */
    public function getUri(): string { return $this->uri; }

    /**
     * Lekérdezi az üzenettörzsben tárolt paramétereket.
     * A $_POST szuperglobális tartalmát adja vissza.
     * 
     * @return array
     */
    public function getParsedBody(): array { return $this->post; }

    /**
     * Lekéri a lekérdezési karakterláncból kinyert változókat.
     * Lényegében a $_GET szuperglobális tartalmát adja vissza,
     * kissé megszűrve.
     * 
     * @return array
     */
    public function getQueryParams(): array { return $this->query; }

}

