## ThinkPHP6.0-ARMS

ARMS 阿里云应用监控链路追踪 library for ThinkPHP6.0 plugin library

## 安装

```phpregexp
composer require tinywan/think-arms
```

## 配置

### 发布配置

```phpregexp
php think tinywan:arms
```
这将自动生成 `config/arms.php` 配置文件。

### 配置中间件

全局中间件在app目录下面`middleware.php`文件中定义，使用下面的方式：

```php
return [
	\tinywan\middleware\ArmsMiddleware::class,
];
```

全局路由中间件`config/route.php`文件中定义，使用下面的方式：

```php
return [
    // 路由中间件全局执行
    'middleware'     => [
        \tinywan\middleware\ArmsMiddleware::class
    ]
];
```

## 使用案例

### [GuzzleHttp\Client](https://github.com/guzzle/guzzle)

```php
$client = new \GuzzleHttp\Client($uri = 'https://api.github.com/repos/guzzle/guzzle');
try {
    $options = ['json' => []];
    $config = [];
    $headers = [];
    if ($uri !== '/oauth/token') {
        $_accessToken = self::_issueAccessToken($config);
        if (false === $_accessToken) {
            return self::setError(false, self::getMessage());
        }
        $headers = array_merge(['Authorization' => 'Bearer ' . $_accessToken], $header);
        $options = array_merge(['headers' => $headers], $options);
    }
    request()->zipKin->addChildSpan('服务调用', [
        'uri' => $uri,
        'method' => $method,
        'headers' => json_encode($headers),
        'body' => json_encode($body),
    ]);
    $resp = $client->request($method, $uri, $options);
} catch (RequestException $e) {
    if ($e->hasResponse()) {
        if (200 != $e->getResponse()->getStatusCode()) {
            $jsonStr = $e->getResponse()->getBody()->getContents();
            $content = json_decode($jsonStr, true);
            return self::setError(false, '温馨提示：' . $content['msg'] ?? '未知的错误信息');
        }
    }
    return self::setError(false, '系统中心提示：' . $e->getMessage());
}
request()->zipKin->finishChildSpan();

$jsonStr = $resp->getBody()->getContents();
```
