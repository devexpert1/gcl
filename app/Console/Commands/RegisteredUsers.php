<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;


class RegisteredUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registered:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         DB::table('users')
           ->where('id', 1)
           ->update(['status' => '2']);
           $vendorss = DB::table('users')->where('status','!=','2')->get();
           foreach($vendorss as $vendors)
           {
               if($vendors->package_id == '1')
               {
                   $expiry = DB::table('users_hours')->where('user_id',$vendors->id)->select('expiry')->first();
                    
                   if(isset($expiry->expiry) && date('Y-m-d') >= date('Y-m-d',strtotime($expiry->expiry))){
                       $start_date =date('Y-m-d');  
                       $date = strtotime($start_date);
                       $date = date('Y-m-d',strtotime("+7 day", $date));  
                       $update_question_count=array(
                       'total_hours'=>'00:10:00',
                       'package_id'=>'1',
                       'expiry'=>$date,
                       'current_question_count'=>'0',
                       );
                       DB::table('users_hours')->where('user_id',$vendors->id)->update($update_question_count);
                       $transaction_data=array(
                       'transaction_id'=>'0',
                       'user_id'=>$vendors->id,
                       'package_id'=>'1',
                       'status'=>'completed',
                       'currency'=>"",
                       'amount'=>'0',
                       'walletuse'=>'0',
                       'exp'=>$date
                       );
                        DB::table('transaction')->insert($transaction_data);
                   }
               }
           }
    }
}
