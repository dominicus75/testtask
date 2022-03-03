<?php

namespace Dominicus75\Http;

trait MessageTrait
{

    private array $headers = [];

    /**
     * Karakterláncként adja vissza a HTTP protokoll verziószámát.
     * 
     * @return string
     */
    public function getProtocolVersion(): string {
        return explode('/', $_SERVER['SERVER_PROTOCOL'])[1];
    }

    /**
     * A paraméterül kapott többől beállítja az üzenet fejléceit
     * 
     * @param array $headers
     */
    private function setHeaders(array $headers): void {
        foreach($headers as $key => $value){ $this->addHeader($key, $value); }
    }

    /**
     * A kapott szövegből kigyomlálja a nem odavaló karaktereket és
     * visszaadja a megtisztított karakterláncot.
     * 
     * @param string $name
     */
    private function sanitizeHeaderName(string $name): string {
        return trim(preg_replace("/^[^\w_\-]*$/i", "", $name));
    }

    /**
     * A kapott szövegből kigyomlálja a nem odavaló karaktereket és
     * visszaadja a megtisztított karakterláncot.
     * 
     * @param string $value
     */
    private function sanitizeHeaderValue(string $value): string {
        return trim(preg_replace("/^[^\w\s\-\?\!\:\.\,\;\/\"\'\^\*\+=@&\{\}\(\)\[\]]*$/i", "", $value));
    }

    /**
     * Lekérdezi az üzenet összes fejlécének értékét.
     * 
     * @return string[][] az üzenet fejléceit tartalmazó asszociatív tömbbel tér vissza. 
     */
    public function getHeaders(): array { return $this->headers; }

    /**
     * Ellenőrzi, hogy a kért fejléc létezik-e a megadott nem kis-, és nagybetű
     * érzékeny néven.
     * 
     * @param string $name a keresett fejléc neve
     */
    public function hasHeader(string $name): bool {
        return array_key_exists($name, $this->headers);
    }

    /**
     * Új fejlécet ad hozzá az objektumhoz
     * 
     * @param string $name a fejléc neve
     * @param string $value a fejléc értéke
     * 
     * @return bool ha a megadott fejléc már létezik, akkor false-al tér vissza
     */
    public function addHeader(string $name, string $value): bool {

        if($this->hasHeader($name)) { return false; }

        $name  = $this->sanitizeHeaderName($name);
        $value = $this->sanitizeHeaderValue($value);
        $this->headers[$name] = $value;

        return true;

    }

    /**
     * Visszaadja a megadott fejlécnévhez tartozó értéket.
     * 
     * @param string $name nem kis-, és nagybetű érzékeny fejlécnév.
     * @return null|string a névhez tartozó érték
     */
    public function getHeader(string $name): ?string {
        return $this->hasHeader($name) ? $this->headers[$name] : null;
    }

}