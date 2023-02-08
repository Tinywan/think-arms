<?php
/**
 * @desc ArmsMiddleware.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/12/1 19:32
 */
declare(strict_types=1);

namespace tinywan\middleware;

use Closure;
use think\facade\Config;
use think\facade\Db;
use think\Request;
use think\Response;
use tinywan\exception\ZipKinException;
use tinywan\ZipKin;

class ArmsMiddleware
{
    /**
     * @desc: handle 描述
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws ZipKinException
     * @author Tinywan(ShaoBo Wan)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = Config::get('arms', []);
        $zipKin = ZipKin::getInstance($config['endpoint_url'] ?? '', $config['app_name'] ?? 'default');
        $zipKin->startRootSpan($request->controller(), $request->method(), $request->param());
        $request->zipKin = $zipKin;
        $request->tracer = $zipKin->getTracing()->getTracer();
        $request->tracing = $zipKin->getTracing();
        $request->childSpan = $zipKin->getChildSpan();
        $request->traceId = $zipKin->getTraceId();
        Db::listen(function ($sql, $time) use ($zipKin) {
            if (0 !== strpos($sql, 'CONNECT:')) {
                $childName = 'sql.query';
                if (str_contains($sql, 'INSERT')) {
                    $childName = 'sql.inert';
                } elseif (str_contains($sql, 'UPDATE')) {
                    $childName = 'sql.update';
                }
                $zipKin->addChildSpan($childName, $sql, (int) ((microtime(true) - $time) * 1000 * 1000));
                $zipKin->finishChildSpan();
            }
        });
        $response = $next($request);
        $response->header(['X-Trace-Id' => $request->traceId]);
        $zipKin->endRootSpan(['http.status_code' => (string) $response->getCode()]);
        return $response;
    }
}