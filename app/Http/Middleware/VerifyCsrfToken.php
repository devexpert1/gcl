<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'stripecoonect','ipn','user/addquestions','App/login_user','App/register_user','App/forgot_password_user','App/addquestions/*','App/editquestions/*','App/reffer_friend',
        'App/getsearch','App/question_delete','App/update_profile/*','App/uploadfile/*','App/checkquestion','App/getquestions/*','App/addsugestion/*','App/facebooklogin',
        'App/googlelogin','App/applyamount/*','App/adddropdowns','App/update_hours/*','App/notificationRead','App/notifocation_delete','App/loginotp','App/uploadfile2', 'stripe_subscription_updated'
    ];
}
