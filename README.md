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

/* HTTP Request to the backend */

$client = new \GuzzleHttp\Client($uri = 'https://api.github.com/repos/guzzle/guzzle');
try {
    $options = ['json' => []];
    $config = [];
    $headers = $header = [];
    if ($uri !== '/oauth/token') {
        $_accessToken = self::_issueAccessToken($config);
        if (false === $_accessToken) {
            return self::setError(false, self::getMessage());
        }
        $headers = array_merge(['Authorization' => 'Bearer ' . $_accessToken], $header);
        /* Injects the context into the wire */
        $injector = request()->tracing->getPropagation()->getInjector(new \Zipkin\Propagation\Map());
        $injector(request()->childSpan->getContext(), $headers);
        $options = array_merge(['headers' => $headers], $options);
    }
    // 添加子节点
    request()->childSpan->annotate('request_started', \Zipkin\Timestamp\now());
    $resp = $client->request($method, $uri, $options);
} catch (RequestException $e) {
    return self::setError(false, '系统中心提示：' . $e->getMessage());
}

// 请求结束
request()->childSpan->annotate('request_finished', \Zipkin\Timestamp\now());
// 结束子节点
request()->zipKin->finishChildSpan();
// 或者 request()->childSpan->finish();

$jsonStr = $resp->getBody()->getContents();
```

### 创培中心使用

```php
$request = new \think\Request();

// 获取所有请求
$carrier = array_map(function ($header) {
    return $header[0];
}, $request->headers->all());

/* Extracts the context from the HTTP headers */
$extractor = $tracing->getPropagation()->getExtractor(new \Zipkin\Propagation\Map());
$extractedContext = $extractor($carrier);
```
