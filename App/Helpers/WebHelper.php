<?php
    namespace App\Helpers;

    use CurlHandle;
    use DOMDocument;
    use DOMXPath;

    /** This class provides some useful methods related to web page crawling */
    class WebHelper {
        /** @var string A prefix which allows to get correctly encoded page content */
        protected const XML_UTF8_PREFIX = '<?xml encoding="UTF-8">';

        /** @var string Xpath selector to get any HTML content which may contain text */
        public const XPATH_TEXT_ELEMENTS = "//*[not(ancestor-or-self::*[@data-noindex " .
            "or self::acronym or self::applet or self::area or self::audio or " .
            "self::base or self::basefont or self::big or self::br or self::canvas or " .
            "self::center or self::col or self::colgroup or self::data or " .
            "self::datalist or self::dir or self::embed or self::fieldset or " .
            "self::figure or self::font or self::frame or self::frameset or self::head " .
            "or self::hr or self::iframe or self::img or self::input or self::link or " .
            "self::map or self::meta or self::noframes or self::noscript or " .
            "self::object or self::optgroup or self::output or self::param or " .
            "self::picture or self::rp or self::ruby or self::script or self::select " .
            "or self::source or self::strike or self::style or self::svg or " .
            "self::template or self::textarea or self::tfoot or self::track or " .
            "self::tt or self::video or self::wbr])]/text()";

        /**
        * @return string Current site domain with protocol
        */
        public static function domain(): string {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            return ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        }

        /**
        * @param CurlHandle $ch Curl handle
        * @param string $domain Current domain
        * @param string $pUrl Page url; overwritten in case of 302 redirect
        * @return ?DOMXPath XPath of the page
        */
        public static function getXPath(
            CurlHandle $ch,
            string $domain,
            string &$pUrl
        ): ?DOMXPath {
            if (!self::isLocalHTMLPage($ch, $domain, $pUrl)) {
                return null;
            }

            curl_setopt($ch, CURLOPT_URL, $domain . $pUrl);
            curl_setopt($ch, CURLOPT_NOBODY, false);

            $rawHTML = curl_exec($ch);
            if (!$rawHTML) {
                return null;
            }

            $dom = new DOMDocument();
            $dom->loadHTML(self::XML_UTF8_PREFIX . $rawHTML);

            return new DOMXpath($dom);
        }

        /**
        * @param CurlHandle $ch Curl handle
        * @param string $domain Current domain
        * @param string $pUrl Page URL; overwritten in case of 302 redirect
        * @return bool True if the effective (possibly redirected) URL belongs to the
        * current domain
        */
        public static function isLocalHTMLPage(
            CurlHandle $ch,
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
            if ($httpCode != 200) {
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

        /**
        * @param DOMXPath $xpath XPath object
        * @param string $query XPath query
        * @return string All text of the elements joined by spaces
        */
        public static function rawTextXPath(DOMXPath $xpath, string $query): string {
            $nodes = $xpath->query($query);
            $text = implode(' ', array_column(iterator_to_array($nodes), 'nodeValue'));

            return TokenHelper::cleanUp($text);
        }
    }