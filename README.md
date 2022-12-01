## think-arms

Think ARMS 阿里云应用监控链路追踪组件

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