<?php

namespace Starline;

use GuzzleHttp\{Client, Exception\GuzzleException, RequestOptions};
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * Class Starline
 * @package Starline
 * @author kowapssupport@gmail.com
 */
class Starline {

    /** @var Config */
    private $config;
    /** @var LoggerInterface */
    private $logger;

    //Таймаут в секундах на выполнение запроса Guzzle.
    protected const GUZZLE_TIMEOUT_SECONDS = 15;

    /**
     * Выполнение команд управления охранно-телематическим комплексом.
     * @see https://developer.starline.ru/#api-Administration-SetParam
     * @param string $slnet_token
     * @param string $device_id
     * @param array $params
     * @return array
     * @throws GuzzleException
     */
    public function runQuery(string $slnet_token, string $device_id, array $params = []): array {
        $response = $this->getClient()->request('POST', 'https://developer.starline.ru/json/v1/device/'.$device_id.'/set_param', [
            RequestOptions::JSON => $params,
            RequestOptions::HEADERS => ['Cookie' => 'slnet='.$slnet_token],
        ]);
        /** @noinspection DuplicatedCode */
        $content = $response->getBody()->getContents();
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return [];
        }
        $object = json_decode($content, true);
        $code = (string)$object['code'] ?? '';
        if (empty($code)) {
            $this->logError('Error response', ['method' => __METHOD__, 'response_object' => $object]);
            return [];
        }
        return $object;
    }

    /**
     * UserData - Получение данных устройств пользователя
     * @see https://developer.starline.ru/#api-UserData-UserData
     * @param string $slnet_token
     * @param string $user_token
     * @param int $user_id
     * @return array
     * @throws Exception|GuzzleException
     */
    public function fetchDevicesInfo(string $slnet_token, string $user_token, int $user_id): array {
        if (empty($slnet_token) || empty($user_token || !$user_id)) {
            throw new Exception('Incorrect param values.');
        }
        $response = $this->createGetRequest('https://developer.starline.ru/json/v3/user/'.$user_id.'/data', [], [
            'Cookie' => 'slnet='.$slnet_token,
        ]);
        $content = $response->getBody()->getContents();
        /** @noinspection DuplicatedCode */
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return [];
        }
        $object = json_decode($content, true);
        $code = (string)$object['code'] ?? '';
        if (empty($code)) {
            $this->logError('Error response', ['method' => __METHOD__, 'response_object' => $object]);
            return [];
        }
        return $object;
    }

    /**
     * Запрос на авторизацию в Starline NET.
     * @see https://developer.starline.ru/#api-Authorization-userSLNETAuth
     * @param string $user_token
     * @return array [$slnet, $user_id]
     * @throws Exception|GuzzleException
     */
    public function fetchSLNETToken(string $user_token): array {
        $response = $this->getClient()->request('POST', 'https://developer.starline.ru/json/v2/auth.slid', [
            RequestOptions::JSON => ['slid_token' => $user_token],
        ]);
        $content = $response->getBody()->getContents();
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return [];
        }
        $object = json_decode($content, true);
        $code = (string)$object['code'] ?? '';
        $user_id = (string)$object['user_id'] ?? '';
        if (empty($code) || $code !== "200" || empty($user_id)) {
            $this->logError('Error response', ['method' => __METHOD__, 'response_object' => $object]);
            return [];
        }
        $cookies = $response->getHeaders()['Set-Cookie'] ?? [];
        if (empty($cookies)) {
            $this->logError('SLNET not found in response cookies', ['method' => __METHOD__, 'headers_object' => $response->getHeaders()]);
            return [];
        }
        $cookie_exploded = explode('; ', $cookies[0] ?? []);
        $first_cookie = $cookie_exploded[0] ?? '';
        if (mb_strpos($first_cookie, 'slnet') === false) {
            $this->logError('SLNET not found in response cookies', ['method' => __METHOD__, 'headers_object' => $response->getHeaders()]);
            return [];
        }
        $exploded_slnet = explode('=', $first_cookie);
        $slnet = $exploded_slnet[1] ?? '';
        if (empty($slnet)) {
            $this->logError('SLNET not found in response cookies', ['method' => __METHOD__, 'headers_object' => $response->getHeaders()]);
            return [];
        }
        return [$slnet, $user_id];
    }

    /**
     * SLID - Авторизация пользователя
     * @see https://id.starline.ru/apiV3/user/login
     * @param string $token
     * @param string $user_ip
     * @return string
     * @throws Exception|GuzzleException
     */
    public function fetchUserToken(string $token, string $user_ip = ''): string {
        /** @noinspection PhpUnhandledExceptionInspection */
        $config = $this->getConfig();
        $request_params = [
            'login' => $config->getLogin(),
            'pass' => sha1($config->getPassword()),
        ];
        if (!empty($user_ip)) {
            $request_params['user_ip'] = $user_ip;
        }
        $response = $this->createPostRequest('https://id.starline.ru/apiV3/user/login', $request_params, [
            'token' => $token,
        ]);
        $content = $response->getBody()->getContents();
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return '';
        }
        $object = json_decode($content, true);
        $state = $object['state'] ?? false;
        if ($state === false || $state !== 1) {
            $this->logError('fetchUserToken error response', ['method' => __METHOD__, 'response_object' => $object]);
            return '';
        }
        return $object['desc']['user_token'] ?? '';
    }

    /**
     * SLID - Получение кода приложения
     * @see https://developer.starline.ru/#api-SLID-getAppCode
     * @return string
     * @throws Exception|GuzzleException
     */
    public function fetchCode(): string {
        /** @noinspection PhpUnhandledExceptionInspection */
        $config = $this->getConfig();
        $secret = md5($config->getSecret());
        $response = $this->createGetRequest('https://id.starline.ru/apiV3/application/getCode', [
            'appId' => $config->getAppId(),
            'secret' => $secret,
        ]);
        $content = $response->getBody()->getContents();
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return '';
        }
        $object = json_decode($content, true);
        $code_key = $object['desc']['code'] ?? false;
        if (!is_string($code_key)) {
            $this->logError('Code not found in response.', ['method' => __METHOD__, 'response_object' => $object]);
            return '';
        }
        return $code_key;
    }

    /**
     * SLID - Получение токена приложения.
     * @see https://developer.starline.ru/#api-SLID-getAppToken
     * @param string $code
     * @return string
     * @throws Exception|GuzzleException
     */
    public function fetchToken(string $code): string {
        if (empty($code)) {
            return '';
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $config = $this->getConfig();
        $secret = md5($config->getSecret().$code);
        $response = $this->createGetRequest('https://id.starline.ru/apiV3/application/getToken', [
            'appId' => $config->getAppId(),
            'secret' => $secret,
        ]);
        $content = $response->getBody()->getContents();
        if (!$this->checkResponse($response, $content, __METHOD__)) {
            return '';
        }
        $object = json_decode($content, true);
        $token_key = $object['desc']['token'] ?? false;
        if (!is_string($token_key)) {
            $this->logError('Token not found in response api', ['method' => __METHOD__, 'response_object' => $object]);
            return '';
        }
        return $token_key;
    }

    protected function checkResponse(ResponseInterface $response, string $content, string $method = ''): bool {
        //status code != 200
        if ((int)$response->getStatusCode() !== 200) {
            $this->logError('Respond status code: ' . $response->getStatusCode(), ['method' => $method]);
            return false;
        }
        if (empty($content)) {
            $this->logError('Response is empty: ' . $content, ['method' => $method, 'content' => $content]);
            return false;
        }
        return true;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param Config $config
     * @return $this
     */
    public function setConfig(Config $config): self {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Config
     * @throws Exception
     */
    public function getConfig(): Config {
        if ($this->config === null) {
            throw new Exception('Logger not set, '. Config::class);
        }
        return $this->config;
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function createPostRequest(string $url, array $params = [], array $headers = []): ResponseInterface {
        return $this->getClient()->request('POST', $url, [
            'form_params' => $params,
            'headers' => $headers,
        ]);
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function createGetRequest(string $url, array $params = [], array $headers = []): ResponseInterface {
        return $this->getClient()->get($url, [
            'query' => $params,
            'headers' => $headers,
        ]);
    }

    protected function getClient(): Client {
        return new Client(['timeout'  => static::GUZZLE_TIMEOUT_SECONDS]);
    }

    /**
     * @param string $message
     * @param array $params
     */
    protected function logError(string $message, array $params = []): void {
        if ($this->logger === null) {
            return;
        }
        $this->logger->logError($message, $params);
    }
}
