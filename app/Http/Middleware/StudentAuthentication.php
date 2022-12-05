<?php

namespace App\Http\Middleware;

use App\Models\Student as ModelsStudent;
use App\Traits\ApiResponser;
use Closure;

class StudentAuthentication
{
    use ApiResponser;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            if ($request->header('Accept-Language')) {
                app()->setLocale($request->header('Accept-Language'));
            }
            $student = ModelsStudent::where('token', '=', $token)->first();
            if ($student) {
                if ($student->blocked == 1) {
                    $student->token = null;
                    $student->logout();
                    $student->save();
                    return $this->success([], __('main.blocked_student_success'));
                }
                return $next($request);
            }
            if (empty($student)) {
                return $this->error(__('main.please_login_again'), 401);
            }
            // return $next($request);
        }
        return $this->error(__('main.account_is_not_found'), 401);
    }
}
