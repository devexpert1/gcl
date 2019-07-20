<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Clear Cache facade value:
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache facade value cleared</h1>';
});

//Reoptimized class loader:
Route::get('/optimize', function() {
    $exitCode = Artisan::call('optimize');
    return '<h1>Reoptimized class loader</h1>';
});

//Route cache:
Route::get('/route-cache', function() {
    $exitCode = Artisan::call('route:cache');
    return '<h1>Routes cached</h1>';
});

//Clear Route cache:
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
});

//Clear View cache:
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return '<h1>View cache cleared</h1>';
});

//Clear Config cache:
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Clear Config cleared</h1>';
});

  Route::get('/', function () {
header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 $data['options'] = App\Options::getoption();
 $data['testimonials'] = App\Testimonials::getoption();
    return view('index',$data);
});
Route::get('/paypaltest', function() {
    
            return view('auth.tes_form');  
});
Route::get('/googlepay', function() {
    
            return view('auth.googlepay');  
});

Route::get('/paypaltest2', function() {
    
            return view('user.tes_form2');  
});


Route::match(['get', 'post'],'stripecoonect', 'Controller@stripecoonect')->name('stripecoonect');
Route::match(['get', 'post'],'payplapayout', 'UserController@payplapayout')->name('payplapayout');
Route::match(['get', 'post'],'payplapayout2', 'Controller@payplapayout2')->name('payplapayout2');
Route::match(['get', 'post'],'simplePay', 'UserController@simplePay')->name('simplePay');
Route::match(['get', 'post'],'ipn', 'Controller@ipn')->name('ipn');

Route::post('/loginotp', 'UserController@loginotp')->name('otp.login');

Route::get('getname/{table}/{id}', 'Controller@getname');
Route::get('check-exists/{table}/{column}/{email}', 'Controller@checkExistsAjax');
Route::get('check-exists-update/{table}/{id}/{column}/{email}', 'Controller@checkExistsAjaxUpdate');
Route::get('check-exists-update2/{table}/{id}/{column}/{email}/{uid}', 'Controller@updateData2');
Route::get('check-exists-update3/{table}/{id}/{column}/{email}', 'Controller@updateData3');

Auth::routes(['verify' => true]);
Route::get('register', 'HomeController@showRegistrationForm');
Route::post('/stripe_subscription_updated', 'HomeController@stripe_subscription_updated');
Route::get('/getinvitation/{id}', 'HomeController@getinvitations')->name('getinvitations');
Route::get('/getinvitations2/{id}', 'HomeController@getinvitations22')->name('getinvitations22');
Route::get('/getuserregister/{sbid}/{id}', 'HomeController@getuserregister')->name('getuserregister');
Route::get('/getuserregister2/{sbid}/{id}', 'HomeController@getuserregister2')->name('getuserregister2');
Route::post('/reffer_friend', 'HomeController@reffer_friend')->name('reffer_friend');
Route::get('/home', 'HomeController@index')->name('home');
Route::get('/myprofile', 'HomeController@myprofile')->name('myprofile');
Route::get('/question', 'HomeController@question')->name('question');
Route::get('/question22', 'HomeController@question22')->name('question22');
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@question'));
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@question22'));
Route::get('/attempt_test', 'HomeController@attempt_test')->name('attempt_test');
Route::get('/subscription', 'HomeController@subscription')->name('subscription');
Route::get('/question-details', 'HomeController@question_details')->name('question-details');
Route::get('/start_test', 'HomeController@start_test')->name('start_test');
Route::get('/about', 'HomeController@about')->name('about');
Route::get('/contact', 'HomeController@contact')->name('contact');
Route::get('/pricing', 'HomeController@pricing')->name('pricing');
Route::get('/faq', 'HomeController@faq')->name('faq');
Route::get('/test-summary', 'HomeController@test')->name('test-summary');
Route::post('/contactus', 'HomeController@contactus')->name('contactus');
Route::post('/subscribe', 'HomeController@subscribe')->name('subscribe');
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@faq'));
Route::post('/register_package/{id}', 'UserController@register')->name('user.register');
Route::get('/referral', 'HomeController@referral')->name('referral');
Route::get('/user/getSignOut', 'UserController@getSignOut')->name('getSignOut');
Route::post('/user/checkemail', 'UserController@checkemail')->name('checkemail');
Route::post('/user/update_hours', 'UserController@update_hours')->name('update_hours');
Route::post('/user/update_plan', 'UserController@update_plan')->name('update_plan');
Route::post('/user/facebooklogin', 'UserController@facebooklogin')->name('facebooklogin');
Route::post('/user/googlelogin', 'UserController@googlelogin')->name('googlelogin');
Route::post('/user/forgetpass', 'UserController@forgetpass')->name('forgetpass');
Route::match(['get', 'post'],'reset_password/{id}', 'UserController@reset_password')->name('reset_password');
Route::get('reset_passwords/{id}', 'UserController@reset_passwords')->name('reset_passwords');
Route::post('resetpassword', 'UserController@resetpassword')->name('resetpassword');
Route::post('/user/profile', 'UserController@profile')->name('profile');
Route::post('/user/uploadfile', 'UserController@uploadfile')->name('uploadfile');
Route::post('/user/addquestions', 'UserController@addquestions')->name('addquestions');
Route::get('/user/questionlist', 'UserController@questionlist')->name('questionlist');
Route::get('/user/editquestion/{id}', 'UserController@editquestion')->name('editquestion');
Route::match(['get','post'],'/user/editquestions', 'UserController@editquestions')->name('editquestions');
Route::get('/user/viewquestion/{id}', 'UserController@viewquestion')->name('viewquestion');
Route::post('/user/delete/{id}/{id2}', 'UserController@delete')->name('delete');
Route::get('/user/getquestions', 'UserController@getquestions')->name('getquestions');
Route::post('/user/applyamount', 'UserController@applyamount')->name('applyamount');
Route::get('/user/hours_left', 'UserController@hours_left')->name('hours_left');

Route::post('/user/addsugestion', 'UserController@addsugestion')->name('addsugestion');
Route::post('/user/update_user_info', 'UserController@update_user_info')->name('update_user_info');
Route::get('/user/attempttest', 'UserController@attempttest')->name('attempttest');
Route::get('/user/report', 'UserController@report')->name('report');
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@report'));
Route::get('/user/getsearch', 'UserController@getsearch')->name('getsearch');
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@getsearch'));
Route::get('/user/test_detail/{id}', 'UserController@test_detail')->name('test_detail');
Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@test_detail'));
Route::get('/user/referral', 'UserController@referral')->name('referral');
Route::get('/user/wallet', 'UserController@wallet')->name('wallet');
 Route::get('/user/notification', 'UserController@notification')->name('notification');
  Route::post('/user/deletenotifications', 'UserController@deletenotifications')->name('deletenotifications');
Route::post('/user/paypal_rec', 'UserController@paypal_rec')->name('paypal_rec');
Route::post('/user/stripe_update_plan', 'UserController@stripe_update_plan')->name('stripe_update_plan');

Route::post('/user/stripe', 'UserController@stripe')->name('stripe');
Route::post('/user/postPaymentWithStripe', 'UserController@postPaymentWithStripe')->name('postPaymentWithStripe');

Route::match(['get', 'post'],'/user/paypal_response', 'UserController@paypal_response')->name('paypal_response');
Route::post('/setsession/{id}', 'HomeController@setsession')->name('setsession');
Route::get('/registered/{id}', 'HomeController@registered')->name('registered');
Route::post('/user/adddropdowns', 'UserController@adddropdowns')->name('adddropdowns');
 Route::get('/getview', 'UserController@getview')->name('admin.getview');
 Route::get('/user/cancelrecurring/{id}', 'UserController@cancelrecurring')->name('cancelrecurring');
 Route::get('/user/cancelrecurring_stripe/{id}', 'UserController@cancelrecurring_stripe')->name('cancelrecurring_stripe');
  Route::get('/user/cancelpaypal/{id}', 'UserController@cancelpaypal')->name('cancelpaypal');
   Route::get('/user/cancelstripe/{id}', 'UserController@cancelstripe')->name('cancelstripe');
 
 Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@getview'));
Route::prefix('admin')->group(function() {
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Pragma: no-cache');
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    
    
  if(!Auth::User())
 { 
  Route::get('', 'Auth\AdminLoginController@logincheck')->name('admin.logincheck');
    Route::get('/login', function() {
    return view('auth.admin-login');
});
}
Route::get('/admin', function() {
      if(session()->exists('admin'))
        { 
    $data['users'] =App\User::getrecentuser();
    $data['totalque']=App\Question_answers::countall();
    $data['totaluser']=App\User::getusercount();
    $data['totaltest']=App\Test::testcount();
       
    return view('/admin/admin-home',$data);
        }else
        {
            return view('auth.admin-login');  
        }
});
 Route::get('/login', 'Auth\AdminLoginController@logincheck')->name('admin.login');
  Route::post('/login', 'Auth\AdminLoginController@logincheck')->name('admin.login');

Route::post('/getreports', 'AdminController@getreports')->name('admin.getreports');
Route::post('/agetquestiondata', 'AdminController@agetquestiondata')->name('admin.agetquestiondata');
Route::post('/getattempttest', 'AdminController@getattempttest')->name('admin.getattempttest');
 Route::get('/user_test_details/{id1}/{id2}/{id3}', 'AdminController@user_test_details')->name('admin.user_test_details');
 Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@user_test_details'));
    Route::post('/login', 'Auth\AdminLoginController@login')->name('admin.login.submit');
   Route::get('/dashboard', 'AdminController@index')->name('admin.home');
    Route::post('/logout', 'Auth\AdminLoginController@logout')->name('admin.logout');
    Route::get('/subscription', 'AdminController@subscription')->name('admin.subscription');
    Route::get('/subscription_list', 'AdminController@subscription_list')->name('admin.subscription_list');
    Route::get('/questions', 'AdminController@questions')->name('admin.questions');
    Route::get('/users', 'AdminController@users')->name('admin.users');
    Route::get('/user_details/{id}', 'AdminController@user_details')->name('admin.user_details');
    Route::get('/total_test/{id}', 'AdminController@total_test')->name('admin.total_test');
    Route::get('/reports', 'AdminController@reports')->name('admin.reports');
    Route::get('/reports/{id}', 'AdminController@reports')->name('admin.reports');
    Route::get('/country', 'AdminController@country')->name('admin.country');
    Route::get('/state_province', 'AdminController@state_province')->name('admin.state_province');
    Route::get('/addfilter_state_province', 'AdminController@addfilter_state_province')->name('admin.addfilter_state_province');
    Route::get('/course', 'AdminController@course')->name('admin.course');
    Route::get('/addfilter_course', 'AdminController@addfilter_course')->name('admin.addfilter_course');
    Route::get('/addfilter_course/{id}', 'AdminController@addfilter_course')->name('admin.addfilter_course');
    Route::get('/grade_level', 'AdminController@grade_level')->name('admin.grade_level');
    Route::get('/addfilter_grade_level', 'AdminController@addfilter_grade_level')->name('admin.addfilter_grade_level');
    Route::get('/addfilter_grade_level/{id}', 'AdminController@addfilter_grade_level')->name('admin.addfilter_grade_level');
    Route::get('/year', 'AdminController@year')->name('admin.year');
    Route::get('/subject', 'AdminController@subject')->name('admin.subject');
    Route::get('/chapter', 'AdminController@chapter')->name('admin.chapter');
    Route::get('/addfilter_country/{id}', 'AdminController@addfilter_country')->name('admin.addfilter_country');
    Route::get('/addfilter_country', 'AdminController@addfilter_country')->name('admin.addfilter_country');
    Route::post('/addfilter_country_store', 'AdminController@addfilter_country_store')->name('admin.addfilter_country_store');
    Route::post('/addfilter_grade_store', 'AdminController@addfilter_grade_store')->name('admin.addfilter_grade_store');
    Route::get('/addfilter_state', 'AdminController@addfilter_state')->name('admin.addfilter_state');
    Route::post('/addfilter_state_store', 'AdminController@addfilter_state_store')->name('admin.addfilter_state_store');
    Route::post('/addfilter_subject_store', 'AdminController@addfilter_subject_store')->name('admin.addfilter_subject_store');
    Route::post('/addfilter_year_store', 'AdminController@addfilter_year_store')->name('admin.addfilter_year_store');
    Route::post('/addfilter_course_store', 'AdminController@addfilter_course_store')->name('admin.addfilter_course_store');
    Route::get('/addfilter_state/{id}', 'AdminController@addfilter_state')->name('admin.addfilter_state');
    Route::get('/addfilter_year', 'AdminController@addfilter_year')->name('admin.addfilter_year');
    Route::get('/addfilter_year/{id}', 'AdminController@addfilter_year')->name('admin.addfilter_year');
    Route::get('/addfilter_subject', 'AdminController@addfilter_subject')->name('admin.addfilter_subject');
    Route::get('/addfilter_subject/{id}', 'AdminController@addfilter_subject')->name('admin.addfilter_subject');
    Route::get('/addfilter_chapter', 'AdminController@addfilter_chapter')->name('admin.addfilter_chapter');
    Route::post('/addfilter_chapter_store', 'AdminController@addfilter_chapter_store')->name('admin.addfilter_chapter_store');
     
     
    Route::post('/add_question', 'AdminController@add_question')->name('admin.add_question');
    Route::get('/addfilter_chapter/{id}', 'AdminController@addfilter_chapter')->name('admin.addfilter_chapter');
    Route::get('/profile', 'AdminController@profile')->name('admin.profile');
    Route::post('/delete/{id}/{id2}', 'AdminController@delete')->name('admin.delete');
     Route::post('/delete111/{id}/{id2}', 'AdminController@delete111')->name('admin.delete111');
     Route::post('/delete112/{id}/{id2}', 'AdminController@delete112')->name('admin.delete112');
     Route::post('/delete1/{id}/{id2}', 'AdminController@delete1')->name('admin.delete1');
    Route::post('/subcat/{id}', 'AdminController@subcategory')->name('admin.subcategory');
    Route::get('/subcat', 'AdminController@subcategory')->name('admin.subcategory');
     Route::get('/getdeta/{id}/{id1}', 'AdminController@getdeta')->name('admin.getdeta');
     Route::get('/editquestion/{id}/{id1}', 'AdminController@editquestion')->name('admin.editquestion');
    
    Route::post('/subchapter/{id}', 'AdminController@subchapter')->name('admin.subchapter');
    Route::get('/subchapter', 'AdminController@subchapter')->name('admin.subchapter');
    
    Route::post('/substate/{id}', 'AdminController@substate')->name('admin.substate');
    Route::get('/substate', 'AdminController@substate')->name('admin.substate');
    Route::post('/uploadfile', 'AdminController@uploadfile')->name('admin.uploadfile');
    Route::post('/uploadprofiless', 'AdminController@uploadprofiless')->name('admin.uploadprofiless');
    Route::post('/addoptions', 'AdminController@addoptions')->name('admin.addoptions');
    Route::get('/contactus', 'AdminController@contactusers')->name('admin.contactusers');
    Route::get('/faqs', 'AdminController@faqs')->name('admin.faqs');
    Route::get('/faq_list', 'AdminController@faq_list')->name('admin.faq_list');
    Route::get('/add_faq', 'AdminController@add_faq')->name('admin.add_faq');
    Route::get('/add_faq/{id}', 'AdminController@add_faq')->name('admin.add_faq');
    Route::post('/add_faq', 'AdminController@add_faq')->name('admin.add_faq');
    Route::post('/add_faq/{id}', 'AdminController@add_faq')->name('admin.add_faq');
    Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@faq_list'));
    Route::get('/about', 'AdminController@about')->name('admin.about');
   // Route::get('/home', 'AdminController@index')->name('admin.home');
     Route::get('/home', function() {
         $data['users'] = App\User::getrecentuser();
    $data['totalque']=App\Question_answers::countall();
    $data['totaluser']=App\User::getusercount();
    $data['totaltest']=App\Test::testcount();
      return view('/admin/admin-home',$data);
});
     Route::get('/home1', 'AdminController@home1')->name('admin.home1');
    Route::get('/testimonials', 'AdminController@testimonials')->name('admin.testimonials');
   Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@testimonials'));
   Route::get('/add_testimonial', 'AdminController@add_testimonial')->name('admin.add_testimonial');
   Route::get('/add_testimonial/{id}', 'AdminController@add_testimonial')->name('admin.add_testimonial');
   Route::post('/add_testimonial', 'AdminController@add_testimonial')->name('admin.add_testimonial');
    Route::post('/testemonialfile', 'AdminController@testemonialfile')->name('admin.testemonialfile');
    Route::get('/referral', 'AdminController@referral')->name('admin.referral');
    Route::get('/settings', 'AdminController@settings')->name('admin.settings');
    Route::get('/total_questions/{id}', 'AdminController@total_questions')->name('admin.total_questions');
     Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@total_questions'));
    Route::post('/uploadprofile', 'AdminController@uploadprofile')->name('admin.uploadprofile');
    Route::post('/editprofile', 'AdminController@editprofile')->name('admin.editprofile');
    Route::post('/updatepassword', 'AdminController@updatepassword')->name('admin.updatepassword');
     Route::get('/usersubscription/{id}', 'AdminController@usersubscription')->name('admin.usersubscription');
     Route::post('/editsubcription', 'AdminController@editsubcription')->name('admin.editsubcription');
     Route::get('/subescribedusers', 'AdminController@subescribedusers')->name('admin.subescribedusers');
    Route::post('/deletesusbscribe/{id1}/{id2}', 'AdminController@deletesusbscribe')->name('admin.deletesusbscribe');
    Route::post('/deletesugesstions/{id1}/{id2}', 'AdminController@deletesugesstions')->name('admin.deletesugesstions');
    Route::get('/footer', 'AdminController@footer')->name('admin.footer');
    Route::get('/pricing', 'AdminController@pricing')->name('admin.pricing');
    Route::get('/withdraw', 'AdminController@withdraw')->name('admin.withdraw');
    Route::get('/request_detail/{id}', 'AdminController@request_detail')->name('admin.request_detail');
    Route::post('/request_detail', 'AdminController@request_detail')->name('admin.request_details');
    Route::post('/requestaction', 'AdminController@requestaction')->name('admin.requestaction');
    Route::get('/loginregister', 'AdminController@loginregister')->name('admin.loginregister');
     Route::get('/notification', 'AdminController@notification')->name('admin.notification');
    Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@notification'));
   Route::post('/deletenotifications', 'AdminController@deletenotifications')->name('admin.deletenotifications');
   Route::post('/deleteall', 'AdminController@deleteall')->name('admin.deleteall');
  Route::get('/edituser/{id}', 'AdminController@edituser')->name('admin.edituser');
  Route::post('/userprofile', 'AdminController@userprofile')->name('admin.userprofile');
  Route::get('/adduser', 'AdminController@adduser')->name('admin.adduser');
  Route::get('/suggested_answers', 'AdminController@suggested_answers')->name('admin.suggested_answers');
  Route::get('/suggestion_detail/{id}', 'AdminController@suggestion_detail')->name('admin.suggestion_detail');
  Route::get('/upload_csv', 'AdminController@upload_csv')->name('admin.upload_csv');
  Route::post('/uploadcsv', 'AdminController@uploadcsv')->name('admin.uploadcsv');
  Route::get('laravel-ajax-pagination',array('as'=>'ajax-pagination','uses'=>'FileController@questions'));
  Route::get('/pay_user/{id}', 'AdminController@pay_user')->name('App.pay_user');
   Route::post('/payuser', 'AdminController@payusers')->name('App.payusers');
});

/* Mobile App its */

Route::prefix('App')->group(function() {
Route::post('/login_user', 'UserController_app@login')->name('App.login');
Route::post('/register_user', 'UserController_app@register')->name('App.register');
Route::post('/forgot_password_user', 'UserController_app@forgetpass')->name('App.forgetpass');
Route::get('/dashboard/{id}', 'UserController_app@dashboard')->name('App.dashboard')->middleware('cors');
Route::get('/questions_list/{id}', 'UserController_app@questions_list')->name('App.questions_list');
Route::post('/addquestions/{id}', 'UserController_app@addquestions')->name('App.addquestions');
Route::post('/editquestions/{id}', 'UserController_app@editquestions')->name('App.editquestions');
Route::post('/question_delete', 'UserController_app@question_delete')->name('App.question_delete');
Route::get('/viewquestion/{id}', 'UserController_app@viewquestion')->name('App.viewquestion');
Route::get('/wallet/{id}/{type}', 'UserController_app@wallet')->name('App.wallet');
Route::post('/reffer_friend', 'UserController_app@reffer_friend')->name('App.reffer_friend');
Route::get('/report/{id}', 'UserController_app@report')->name('App.report');
Route::get('/get_dropdowns/{id}/{type}', 'UserController_app@get_dropdowns')->name('App.get_dropdowns');
Route::post('/getsearch', 'UserController_app@getsearch')->name('App.getsearch');
Route::get('/referral_listing/{id}', 'UserController_app@referral_listing')->name('App.referral_listing');
Route::post('/update_profile/{id}', 'UserController_app@update_profile')->name('App.update_profile');
Route::get('/profile_get/{id}', 'UserController_app@profile_get')->name('App.profile_get');
Route::post('/uploadfile/{id}', 'UserController_app@uploadfile')->name('App.uploadfile');
Route::post('/getquestions/{id}', 'UserController_app@getquestions')->name('App.getquestions');
Route::get('/editquestionview/{id}', 'UserController_app@editquestionview')->name('App.editquestionview');
Route::post('/checkquestion', 'UserController_app@checkquestion')->name('App.checkquestion');
Route::post('/addsugestion/{id}', 'UserController_app@addsugestion')->name('addsugestion');
Route::get('/cancelrecurring/{id}/{userid}', 'UserController_app@cancelrecurring')->name('cancelrecurring');
Route::get('/attempttest/{id}', 'UserController_app@attempttest')->name('attempttest');
Route::get('/notification/{id}', 'UserController_app@notification')->name('notification');
Route::get('/test_detail/{id}/{userid}', 'UserController_app@test_detail')->name('test_detail');
Route::post('/facebooklogin', 'UserController_app@facebooklogin')->name('facebooklogin');
Route::post('/googlelogin', 'UserController_app@googlelogin')->name('googlelogin');
Route::post('/applyamount/{userid}', 'UserController_app@applyamount')->name('applyamount');
Route::post('/adddropdowns', 'UserController_app@adddropdowns')->name('adddropdowns');
Route::post('/update_hours/{userid}', 'UserController_app@update_hours')->name('update_hours');
Route::get('/hours_left/{userid}', 'UserController_app@hours_left')->name('hours_left');
Route::post('/notificationRead/', 'UserController_app@readnotification')->name('readnotification');
Route::post('/notifocation_delete/', 'UserController_app@notifocation_delete')->name('notifocation_delete');
Route::post('/loginotp', 'UserController_app@loginotp')->name('otp.logins');
Route::post('/uploadfile2', 'UserController_app@uploadfile2')->name('App.uploadfile2');
Route::get('/getnotificationcount/{id}', 'UserController_app@getnotificationcount')->name('App.getnotificationcount');
});
