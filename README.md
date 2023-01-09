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

全局中间件在app目录下面middleware.php文件中定义，使用下面的方式：

```php

return [
	\tinywan\middleware\ArmsMiddleware::class,
];
```

## 其他

GuzzleHttp\Client 使用

```
$client = new Client(['base_uri' => $config['app_base_uri']]);
try {
    $options = ['json' => $body];
    $headers = [];
    if ($uri !== '/oauth/token') {
        $_accessToken = self::_issueAccessToken($config);
        if (false === $_accessToken) {
            return self::setError(false, self::getMessage());
        }
        $headers = array_merge(['Authorization' => 'Bearer ' . $_accessToken], $header);
        $options = array_merge(['headers' => $headers], $options);
    }
    request()->zipKin->addChildSpan('中心服务调用', [
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
