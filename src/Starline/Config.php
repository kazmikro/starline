<?php

namespace Starline;

/**
 * Class Config
 * @package Starline
 * @author kowapssupport@gmail.com
 */
class Config {

    private $login = '';
    private $password = '';
    private $app_id = '';
    private $secret = '';

    /**
     * @return string
     */
    public function getLogin(): string {
        return $this->login;
    }

    /**
     * Устанавливает логин пользователя кабинета https://my.starline.ru
     * @param string $login
     * @return $this
     */
    public function setLogin(string $login): self {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string {
        return $this->password;
    }

    /**
     * Устанавливает пароль пользователя кабинета https://my.starline.ru
     * @param string $password
     * @return $this
     */
    public function setPassword($password): self {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppId(): string {
        return $this->app_id;
    }

    /**
     * Устанавливает идентификатор приложения https://my.starline.ru
     * @param string $app_id
     * @return $this
     */
    public function setAppId($app_id): self {
        $this->app_id = $app_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecret(): string {
        return $this->secret;
    }

    /**
     * Устанавливает Secret код приложения https://my.starline.ru
     * @param string $secret
     * @return $this
     */
    public function setSecret($secret): self {
        $this->secret = $secret;
        return $this;
    }
}
