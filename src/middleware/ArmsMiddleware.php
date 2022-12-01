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
        $traceId = $zipKin->getTraceId();
        Db::listen(function ($sql, $runtime) use ($zipKin) {
            $type = 'mysql-select';
            if (str_contains($sql, 'INSERT')) {
                $type = 'mysql-insert';
            } elseif (str_contains($sql, 'UPDATE')) {
                $type = 'mysql-update';
            }
            $zipKin->addChildSpan($sql, $type);
            $zipKin->finishChildSpan();
        });
        $response = $next($request);
        $response->header(['trace_id' => $traceId]);
        $zipKin->endRootSpan(['http.status_code' => $response->getCode()]);
        return $response;
    }
}