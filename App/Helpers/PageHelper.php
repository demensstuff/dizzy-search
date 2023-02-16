<?php
    namespace App\Helpers;

    class PageHelper {
        /** A prefix which allows to get correctly encoded HTML page content */
        const XML_UTF8_PREFIX = '<?xml encoding="UTF-8">';

        /** Xpath selector to get any HTML tag content which may contain text */
        const XPATH_TEXT_ELEMENTS = "//*[not(self::acronym or self::applet or " .
        "self::area or self::audio or self::base or self::basefont or self::big or " .
        "self::br or self::canvas or self::center or self::col or self::colgroup or " .
        "self::data or self::datalist or self::dir or self::embed or self::fieldset or " .
        "self::figure or self::font or self::frame or self::frameset or self::head or " .
        "self::hr or self::iframe or self::img or self::input or self::link or " .
        "self::map or self::meta or self::noframes or self::noscript or self::object or " .
        "self::optgroup or self::output or self::param or self::picture or self::rp or " .
        "self::ruby or self::script or self::select or self::source or self::strike or " .
        "self::style or self::svg or self::template or self::textarea or self::tfoot or " .
        "self::track or self::tt or self::video or self::wbr)]/text()";

        public static function domain() {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            return ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        }

        public static function domainRelativeURL(string $url): ?string {
            $args = parse_url($url);

            $keySet = array_key_exists('scheme', $args);
            if ($keySet && $args['scheme'] != 'http' && $args['scheme'] != 'https') {
                return null;
            }

            $keySet = array_key_exists('host', $args);
            if ($keySet && $args['host'] != $_SERVER['HTTP_HOST']) {
                return null;
            }

            if (!array_key_exists('path', $args)) {
                return null;
            }

            $path = rtrim($args['path'], '/');

            if (array_key_exists('query', $args)) {
                $path .= '?' . rtrim($args['query'], '/');
            }

            return $path;
        }

        public static function getXPath(
            \CurlHandle $ch,
            string $domain,
            string &$pUrl
        ): ?\DOMXPath {
            if (!self::isLocalHTMLPage($ch, $domain, $pUrl)) {
                return null;
            }

            curl_setopt($ch, CURLOPT_URL, $domain . $pUrl);
            curl_setopt($ch, CURLOPT_NOBODY, false);

            $rawHTML = curl_exec($ch);
            if (!$rawHTML) {
                return null;
            }

            $dom = new \DOMDocument();
            $dom->loadHTML(self::XML_UTF8_PREFIX . $rawHTML);

            $xpath = new \DOMXpath($dom);
            if (!$xpath) {
                return null;
            }

            return $xpath;
        }

        public static function isLocalHTMLPage(
            \CurlHandle $ch,
            string $domain,
            string &$pUrl
        ): bool {
            $absUrl = $domain . $pUrl;

            curl_setopt($ch, CURLOPT_URL, $absUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);

            if (!curl_exec($ch)) {
                return false;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (!$httpCode || $httpCode != 200) {
                return false;
            }

            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if ($effectiveUrl != $absUrl) {
                if (!str_starts_with($effectiveUrl, $domain)) {
                    return false;
                }

                $pUrl = substr($effectiveUrl, strlen($domain));
            }

            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            return str_starts_with($contentType, 'text/html');
        }

        public static function rawTextXPath(\DOMXPath $xpath, string $query): string {
            $nodes = $xpath->query($query);
            $text = implode(' ', array_column(iterator_to_array($nodes), 'nodeValue'));

            return TokenHelper::stripWhitespaces($text);
        }

        public static function setGetParam(string $url, string $key, string $val): string {
            $qPos = mb_strpos($url, '?');
            if ($qPos === false) {
                return $url . '?' . $key . '=' . $val;
            }

            $getParams = mb_substr($url, $qPos);
            $regex = "/(^|&)(" . $key . "=)([^&]*)(&|$)/u";

            if (preg_match($regex, $getParams)) {
                $plainUrl = mb_substr($url, 0, $qPos);

                return $plainUrl . preg_replace($regex, "\1\2" . $val . "\4", $getParams);
            }

            return $url . '&' . $key . '=' . $val;
        }
    }
?>