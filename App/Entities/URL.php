<?php

namespace App\Entities;

/** This class represents a parsed URL */
class URL {
    /** @var array[string]null Valid protocols */
    protected const PROTOCOLS = [
        'http'  => null,
        'https' => null
    ];

    /** @var string Scheme */
    protected string $scheme = '';

    /** @var string Domain name */
    protected string $host = '';

    /** @var string URL params */
    protected string $path = '';

    /** @var array[string]string GET params */
    protected array $query = [];

    public function __construct(string $url) {
        $args = parse_url($url);

        if (array_key_exists('scheme', $args)) {
            $this->scheme = $args['scheme'] ?? '';
        }

        if (array_key_exists('host', $args)) {
            $this->host = $args['host'] ?? '';
        }

        if (array_key_exists('path', $args)) {
            $this->path = rtrim($args['path'], '/') ?? '';
        }

        if (array_key_exists('query', $args)) {
            parse_str($args['query'], $this->query);
        }
    }

    /**
     * @return bool Whether the url belongs to the current domain
     */
    public function belongsToDomain(): bool {
        if ($this->scheme != '' && !array_key_exists($this->scheme, self::PROTOCOLS)) {
            return false;
        }

        if ($this->host != '' && $this->host != $_SERVER['HTTP_HOST']) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key GET param key
     * @param string $value GET param value
     * @return void
     */
    public function setGetParam(string $key, string $value): void {
        $this->query[$key] = $value;
    }

    /**
     * @param bool $relative Whether relative URL should be returned
     * @return string String representation of the URL
     */
    public function toString(bool $relative = false): string {
        $str = $this->path;

        if ($this->query) {
            $str .= '?' . http_build_query($this->query);
        }

        return $relative ? $str : $this->scheme . '://' . $this->host . $str;
    }
}