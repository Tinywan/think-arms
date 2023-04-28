## ThinkPHP6.0-ARMS

ARMS 阿里云应用监控链路追踪 library for ThinkPHP6.0 plugin library

## 安装

```php
composer require tinywan/think-arms
```

## 配置

### 发布配置

```php
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
public function requestHandle(string $method, string $uri, array $body = [], array $header = [])
{
    $config = self::_appConfig();
    if (empty($config)) {
        return self::setError(false, '请先在创培服务中心，开通站点配置');
    }

    $client = new Client(['base_uri' => $config['app_base_uri']]);
    try {
        $options = ['json' => $body];
        $tracingHeader = [];
        /* Injects the context into the wire */
        $injector = request()->tracing->getPropagation()->getInjector(new \Zipkin\Propagation\Map());
        $injector(request()->childSpan->getContext(), $tracingHeader);
        $headers = array_merge($tracingHeader, $header);
        if ($uri !== '/oauth/token') {
            $_accessToken = self::_issueAccessToken($config);
            if (false === $_accessToken) {
                return self::setError(false, self::getMessage());
            }
            $headers = array_merge(['Authorization' => 'Bearer ' . $_accessToken], $headers);
        }
        $options = array_merge(['headers' => $headers], $options);
        /* Creates the span for getting Ucenter */
        request()->zipKin->addChildSpan('ucenter:'.$uri, [
            'uri' => $uri,
            'method' => $method,
            'headers' => json_encode($headers),
            'body' => json_encode($body),
        ]);
        /* HTTP Request to the Ucenter */
        request()->childSpan->annotate('request.started', now());
        $resp = $client->request($method, $uri, $options);
    } catch (RequestException | GuzzleException $e) {
        if ($e->hasResponse()) {
            if (200 != $e->getResponse()->getStatusCode()) {
                $jsonStr = $e->getResponse()->getBody()->getContents();
                $content = json_decode($jsonStr, true);
                return self::setError(false, '温馨提示：' . $content['msg'] ?? '未知的错误信息');
            }
        }
        return self::setError(false, '系统中心提示：' . $e->getMessage());
    }
    request()->childSpan->annotate('request.finished', now());
    request()->childSpan->finish();
    request()->zipKin->endRootSpan();
    $jsonStr = $resp->getBody()->getContents();
    $data = json_decode($jsonStr, true);
    if (!isset($data['code']) || 0 != $data['code']) {
        return self::setError(false, $data['msg'] ?? '响应数据结构异常');
    }
    return $data;
}
```
