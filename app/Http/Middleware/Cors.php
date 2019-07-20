<?php namespace App\Http\Middleware;
use Closure;
class Cors
{
  public function handle($request, Closure $next)
  {
     header("Access-Control-Allow-Origin: *");
     $headers = [
         'Access-Control-Allow-Methods' =>'GET, POST, PUT, DELETE, OPTIONS',
         'Access-Control-Allow-Headers'=>  'X-Requested-With, Content-Type, X-Token-Auth, Authorization,Origin'
         ];
    
     if($request->getMethod() == 'OPTIONS')
     {
         return response()->json('OK',200,$headers);
     }
     
     $response = $next($request);
     foreach($headers as $key => $value)
     {
         $response->header($key,$value);
     }
     return $response;
     
  }
  
}