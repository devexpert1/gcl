<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use DB;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
         'App\Console\Commands\RegisteredUsers',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /* $schedule->command('registered:users')
                   ->everyMinute();*/
        // $schedule->command('inspire')
        //          ->hourly();
         $schedule->call(function () {
            
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
             /*DB::table('users')
                        ->where('id', 1)
                        ->update(['status' => '2']);*/
            
        })->everyMinute(); 
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
