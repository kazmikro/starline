![](https://www.starline.ru/wp-content/uploads/2020/08/StarLine-20.png)

# **StarLine Open API **

Реализация работы с Starline Open API.

Примеры работы:

```php
//Настройки доступа из https://my.starline.ru/developer.
$config = (new \Starline\Config())
    ->setLogin('login')//логин пользователя.
    ->setPassword('password')//пароль пользователя.
    ->setAppId('app id')//идентификатор приложения.
    ->setSecret('secret key');//ключ доступа к приложению.

$starline = new \Starline\Starline();
$starline->setConfig($config);
```

Возможные запросы на получение данных:

```php
//Пример получения кода приложения.
$code = $starline->fetchCode();

//Пример получения token ключа.
$token = $starline->fetchToken($code);

//Авторизация пользователя.
$user_token = $starline->fetchUserToken($token);

//Пример получения SLNET token ключа.
[$slnet, $user_id] = $starline->fetchSLNETToken($user_token);

//Пример получения существующих устройств пользователя.
$devices = $starline->fetchDevicesInfo($slnet, $user_token, $user_id);
echo '<pre>';
print_r($devices);
echo '</pre>';

//Пример получения device_id, выберите нужное устройство из массива $devices['user_data']['devices']
$device_id = $devices['user_data']['devices'][0]['device_id'] ?? '';

//Пример выполнения запроса к устройству.
$response = $starline->runQuery($slnet, $device_id, [
    'type' => 'arm',//тип "охраны устройства"
    'arm' => 1,//постановка на охрану
]);
echo '<pre>';
print_r($response);
echo '</pre>';

```
