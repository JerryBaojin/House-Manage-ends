<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Tymon\JWTAuth\Exceptions\TokenInvalidException ;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

// 注意，我们要继承的是 jwt 的 BaseMiddleware
class RefreshToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // return response(["info"=>"请重新登录","code"=>0], 401);
        // 检查此次请求中是否带有 token，如果没有则抛出异常。
        try {
            $this->checkForToken($request);
        } catch (UnauthorizedHttpException $th) {
           return   response(["info"=>"请重新登录","code"=>0], 401);
        }
       // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常

        try {
            // 检测用户的登录状态，如果正常则通过
            if ($this->auth->parseToken()->authenticate()) {
                return $next($request);
            }
        }catch (TokenInvalidException $exception){
             return   response(["info"=>"token无效","code"=>0], 401);
        }catch (TokenExpiredException $exception) {
          // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
            try {
                // 刷新用户的 token,并使之前token过期
                $token = $this->auth->refresh();
                // $this->auth->invalidate($request->header('Authorization'),true);
               // 使用一次性登录以保证此次请求的成功
                Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
            } catch (JWTException $exception) {
               // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                   return   response(["info"=>"请重新登录","code"=>0], 401);
            }
        }
        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
    }
}
