<?php

namespace mendeley;


class Tokens {

    private $accessToken;
    private $expireTime;
    private $refreshToken;

    public function __construct($accessToken, $expireTime, $refreshToken) {
        $this->accessToken = $accessToken;
        $this->expireTime = $expireTime;
        $this->refreshToken = $refreshToken;
    }

    public function getAccessToken() {
        return $this->accessToken;
    }

    public function getExpireTime() {
        return $this->expireTime;
    }

    public function isAccessTokenExpired($gap = 10) {
        return time() >= $this->expireTime - $gap;
    }

    public function getRefreshToken() {
        return $this->refreshToken;
    }
}


class OAuth {

    const AUTHORIZE_URL = 'https://api-oauth2.mendeley.com/oauth/authorize';
    const TOKEN_ENDPOINT = 'https://api-oauth2.mendeley.com/oauth/token';

    private $clientID;
    private $clientSecret;
    private $redirectURI;

    public function __construct($clientID, $clientSecret, $redirectURI) {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->redirectURI = $redirectURI;
    }

    public function getAuthURL() {
        return self::AUTHORIZE_URL
                . '?client_id=' . urlencode($this->clientID)
                . '&redirect_uri=' . urlencode($this->redirectURI)
                . '&response_type=code&scope=all';
    }

    public function exchangeAuthCodeForAccessToken($authCode) {
        $response = HTTP::post(self::TOKEN_ENDPOINT, array(
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'redirect_uri' => $this->redirectURI,
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret));
        return self::decodeTokens($response);
    }

    public function refreshAccessToken($refreshToken) {
        $response = HTTP::post(self::TOKEN_ENDPOINT, array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'redirect_uri' => $this->redirectURI,
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret));
        return self::decodeTokens($response);
    }

    public function getFreshTokens($currentTokens, $gap = 10) {
        if ($currentTokens->isAccessTokenExpired($gap)) {
            return self::refreshAccessToken($currentTokens->getRefreshToken());
        } else {
            return $currentTokens;
        }
    }

    private static function decodeTokens($response) {
        $json = json_decode($response);
        return new Tokens(
                $json->access_token, 
                time() + intval($json->expires_in),
                $json->refresh_token);
    }
}


class Session {

    const API_ENDPOINT_BASE = 'https://api-oauth2.mendeley.com/oapi/';

    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function get($resource) {
        $response = HTTP::get(self::API_ENDPOINT_BASE . $resource,
                array('Authorization: Bearer ' . $this->accessToken));
        return json_decode($response);
    }
}


class HTTP {

    public static function get($url, $headers = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code != 200) {
            throw new \Exception('HTTP error ' . $http_code . "\n" . strip_tags($response));
        }
        return $response;
    }

    public static function post($url, $post_parms = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::params_string($post_parms));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code != 200) {
            throw new \Exception('HTTP error ' . $http_code . "\n" . strip_tags($response));
        }
        return $response;
    }

    private static function params_string($parms) {
        $result = '';
        foreach ($parms as $key => $value) {
            $result .= $key . '=' . urlencode($value) . '&';
        }
        return $result;
    }
}

class DocFormatter {

    private $buffer;

    protected function reset() {
        $this->buffer = '';
        return $this;
    }

    protected function append($data, $delimiters = array()) {
        if (is_array($data)) {
            $data = array_filter($data);
        }
        if ($data) {
            if ($before = $delimiters['before']) {
                $this->buffer .= $before;
            }
            $this->buffer .= is_array($data)
                    ? implode($delimiters['inside'], $data)
                    : $data;
            if ($after = $delimiters['after']) {
                $this->buffer .= $after;
            }
        }
        return $this;
    }

    protected function toString() {
        return $this->buffer;
    }

    public function format($doc) {
        return $this->reset()
            ->append($this->formatAuthors($doc->authors), array('after' => ' '))
            ->append($doc->title)
            ->append($doc->published_in, array('before' => ' // '))
            ->append($doc->issue, array('before' => ', Вып. '))
            ->append($doc->volume, array('before' => ', T. '))
            ->append(array($doc->city, $doc->publisher), array('before' => ' — ', 'inside' => ': '))
            ->append($doc->year, array('before' => ', ', 'after' => '.'))
            ->append($doc->pages, array('before' => ' С. ', 'after' => '.'))
            ->toString();
    }

    protected function formatAuthors($authors) {
        $result = array();
        foreach ($authors as $author) {
            $result[] = $this->formatAuthor($author);
        }
        return implode(', ', $result);
    }

    protected function formatAuthor($author) {
        $initials = $this->getInitials($author->forename);
        $surname = $author->surname;
        return $surname . ' ' . $initials;
    }

    protected function getInitials($name) {
        $names = explode(' ', $name);
        $initials = array_map(
                function($s) {
                    return mb_substr($s, -1) == '.'
                           ? $s
                           : mb_substr($s, 0, 1) . '.'; 
                },
                $names);
        return implode(' ', $initials);
    }
}

?>
