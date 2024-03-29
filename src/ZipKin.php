<?php

/**
 * @desc ZipKin.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/12/1 19:35
 */

declare(strict_types=1);

namespace tinywan;

use tinywan\exception\ZipKinException;
use Zipkin\DefaultTracing;
use Zipkin\Endpoint;
use Zipkin\Propagation\Map;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Span;
use Zipkin\Tracer;
use Zipkin\Tracing;
use Zipkin\TracingBuilder;

class ZipKin
{
    protected static Tracer $tracer;

    protected static Span $rootSpan;

    protected static string $appName = '';

    private static ?ZipKin $instance = null;

    /**
     * @var DefaultTracing
     */
    private static DefaultTracing $tracing;

    private static $span = null;

    private static $childSpan = null;

    private function __construct()
    {
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * @desc: 获取Zipkin实例
     * @param string $httpReporterURL
     * @param string $appName
     * @return ZipKin|null
     * @throws ZipKinException
     * @author Tinywan(ShaoBo Wan)
     */
    public static function getInstance(string $httpReporterURL = '', string $appName = 'default'): ?ZipKin
    {
        if (empty($httpReporterURL)) {
            throw new ZipKinException('链路错误');
        }
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();

            self::$appName = $appName;
            self::$tracing = self::createTracing(self::$appName, $_SERVER['REMOTE_ADDR'], $httpReporterURL);

            /* Extracts the context from the HTTP headers */
            $extractor = self::$tracing->getPropagation()->getExtractor(new Map());
            $extractedContext = $extractor(array_map(function ($param) {
                return $param[0] ?? 'default';
            }, request()->param()));

            self::$tracer = self::$tracing->getTracer();
            self::$rootSpan = self::$tracer->nextSpan($extractedContext);
        }
        return self::$instance;
    }

    /**
     * @desc: 开始一个根span
     * @param $uri
     * @param $method
     * @param $params
     * @author Tinywan(ShaoBo Wan)
     */
    public function startRootSpan($uri, $method, $params)
    {
        self::$rootSpan->setName($uri);
        self::$rootSpan->start();
        self::$rootSpan->tag('http.method', $method);
        self::$rootSpan->tag('params', json_encode($params));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? 'cmd';
        self::$rootSpan->tag('site', $origin);
        $host = $_SERVER['HTTP_HOST'] ?? 'cmd';
        self::$rootSpan->tag('host', $host);
    }

    /**
     * @desc: 结束RootSpan
     * @param array $tagArr
     * @author Tinywan(ShaoBo Wan)
     */
    public function endRootSpan(array $tagArr = [])
    {
        if ($tagArr !== []) {
            foreach ($tagArr as $key => $val) {
                self::$rootSpan->tag($key, $val);
            }
        }
        self::$rootSpan->finish();
        $tracers = self::$tracer;
        register_shutdown_function(function () use ($tracers) {
            $tracers->flush();
        });
    }

    /**
     * @desc: 新增一个子span
     * @param string $childName
     * @param $executeValue
     * @param int|null $time
     * @author Tinywan(ShaoBo Wan)
     */
    public function addChildSpan(string $childName, $executeValue, int $time = null)
    {
        if (self::$span === null) {
            self::$span = self::$rootSpan;
        }
        $childSpan = self::$tracer->newChild(self::$span->getContext());

        $childSpan->start($time);
        $childSpan->setKind(\Zipkin\Kind\CLIENT);
        $childSpan->setName($childName);
        if (is_array($executeValue)) {
            foreach ($executeValue as $key => $val) {
                $childSpan->tag($key, $val);
            }
        } else {
            $tag = 'data';
            if (in_array($childName, ['sql.query', 'sql.exe'])) {
                $tag = 'db.statement';
            }
            $childSpan->tag($tag, $executeValue);
        }
        self::$childSpan = $childSpan;
    }

    /**
     * @desc: 结束子span
     * @param array $tags
     * @author Tinywan(ShaoBo Wan)
     */
    public function finishChildSpan(array $tags = [])
    {
        if (!empty($tags)) {
            foreach ($tags as $key => $val) {
                self::$childSpan->tag($key, $val);
            }
        }
        self::$childSpan->finish();
    }

    /**
     * @desc: 获取链路的唯一标识
     * @author Tinywan(ShaoBo Wan)
     */
    public function getTraceId(): string
    {
        return self::$rootSpan->getContext()->getTraceId();
    }

    /**
     * @desc: 创建一个新链路
     * @param $localServiceName
     * @param $localServiceIPv4
     * @param $httpReporterURL
     * @param null $localServicePort
     * @return DefaultTracing|Tracing
     */
    public static function createTracing($localServiceName, $localServiceIPv4, $httpReporterURL, $localServicePort = null)
    {
        $endpoint = Endpoint::create($localServiceName, $localServiceIPv4, null, $localServicePort);

        $reporter = new \Zipkin\Reporters\Http(['endpoint_url' => $httpReporterURL]);
        $sampler = BinarySampler::createAsAlwaysSample();
        return TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
    }

    /**
     * @desc: getTracing 描述
     * @return DefaultTracing
     * @author Tinywan(ShaoBo Wan)
     */
    public function getTracing(): DefaultTracing
    {
        return self::$tracing;
    }

    /**
     * @desc: getChildSpan 描述
     * @return null
     * @author Tinywan(ShaoBo Wan)
     */
    public function getChildSpan(): ?Span
    {
        if (empty(self::$childSpan)) {
            self::$childSpan = self::$rootSpan;
        }
        return self::$childSpan;
    }
}