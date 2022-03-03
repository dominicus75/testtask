<?php

namespace Dominicus75\Http;


class Response
{

    use MessageTrait;

    const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Reserved for WebDAV advanced collections expired proposal',
        426 => 'Upgrade required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    private int $status;
    private string $reason;
    private string $body;


    public function __construct(){ }

    /**
     * Beállítja az üzenet állapotkódját. Ha a paraméterként átadott
     * kód érvénytelen, akkor kivételt dob.
     * 
     * @param int $code a beállítandó állapotkód
     * @throws \Dominicus75\Http\InvalidStatusException
     */
    public function setStatusCode(int $code): self {

        if(array_key_exists($code, self::PHRASES)){
            $this->status = $code;
            $this->reason = self::PHRASES[$code];
            return $this;
        } else {
            throw new InvalidStatusException();
        }

    }

    /**
     * Lekéri a válaszüzenet állapotkódját.
     *
     * @return int Állapotkód.
     */
    public function getStatusCode(): int { return $this->status; }

    /**
     * Beállítja az üzenet törzsét
     * 
     * @param string $content
     */
    public function setBody(string $content): self {
        $this->body = $content;
        return $this;
    }

    /**
     * Lekérdezi az üzenet törzsét.
     */
    public function getBody(): string { return $this->body; }

    /**
     * A paraméterként kapott címre irányít át
     * 
     * @param string @url az átirányítás célja
     */
    public function redirect(string $url): void {

        $this->setStatusCode(301);
        $this->addHeader('Location', $url);
        $this->send();

    }

    /**
     * Elküldi az üzenet fejléceit
     */
    protected function sendHeaders(): self {

        if(headers_sent()) { return $this; }

        foreach($this->headers as $name => $value) { header($name.': '.$value); }

        header(sprintf('%s %s %s', $_SERVER['SERVER_PROTOCOL'], $this->status, $this->reason), true, $this->status);

        return $this;

    }

    /**
     * Kimenetre küldi az üzenettörzset
     */
    protected function sendBody(): void { echo $this->body; }

    /**
     * Elküldi a válasz üzenetet
     */
    public function send(): void {
        $this->sendHeaders();
        $this->sendBody();
    }


}