<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Common\PhpMailController;

class Reassign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reasign:ticket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reasign:ticket';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PhpMailController $PhpMailController)
    {
        parent::__construct();
        $this->PhpMailController = $PhpMailController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $msg = "hii";
        // mail("madhura.bk@compassitesinc.com","My subject",$msg);

        // $to = "madhura.bk@compassitesinc.com";
        // $subject = "My subject";
        // $txt = "Hello world!";

        // mail($to,$subject,$txt);
        // $emails = array("madhura.bk@compassitesinc.com");
        // $input = Input::all();

        // Mail::send('emails.welcome', array('body' => Input::get('email_body')), 
        // function($message) use ($emails, $input) {
        // $message
        // ->from('jr@compassites.net', 'Administrator')
        // ->subject('Admin Subject');

        // foreach ($emails as $email) {
        //     $message->to($email);
        // }
        // });
        
        // $data = [
        //     'email' => 'mahi.lohi@gmail.com',
        //     'ticket_id' => "554545",
        //     'new_agent' => "nknk"
        // ];

        // $this->PhpMailController->sendmail(
        //     $from = $this->PhpMailController->mailfrom('0', '1'),
        //     $to = ['name' => 'testing', 'email' => 'madhura.bk@compassitesinc.com'],
        //     $message = [
        //         'subject'     => 'kjbjdkcnd',
        //         'body'        => 'nkbdcnksnnk ndcs',
        //         'scenario'    => 'ticket-reply',
        //         'attachments' => null,
        //     ],
        //     $template_variables = [
        //         'ticket_number' => 'ddsww',
        //         'user'          => 'bjvndks',
        //         'agent_sign'    => 'hijknbjuhijknj',
        //         'system_link'   => 'bvjdnkskfe',
        //     ]
        // );

        // try {
        //     $to = $request->input('to');
        //     $subject = $request->input('subject');
        //     $msg = $request->input('message');
        //     $from = $request->input('from');
        //     $from_address = Emails::where('id', '=', $from)->first();
        //     if (!$from_address) {
        //         throw new Exception('Sorry! We can not find your request');
        //     }
        //     $to_address = [

        //         'name'  => '',
        //         'email' => 'madhura.bk@compassitesinc.com',
        //     ];
        //     $message = [
        //         'subject'  => $subject,
        //         'scenario' => null,
        //         'body'     => $msg,
        //     ];

        //     $this->PhpMailController->sendmail($from, $to_address, $message, [], []);

        //     return redirect()->back()->with('success', 'Mail has send successfully');
        // } catch (Exception $e) {
        //     return redirect()->back()->with('fails', $e->getMessage());
        // }
        // exit;
        // Mail::send('emails.test', $data, function ($message) use ($data) {
        //     $message->from('jr@compassites.net', 'Ticketing');
        //     $message->to('madhura.bk@compassitesinc.com')->subject("Testing");;
        //         });
        // exit;
        \Mail::to($user)->send(new Email);
        

        $updatedTime = date("Y-m-d H:i:s");
        file_put_contents('/home/vagrant/Code/helpdesk/public/a.txt', $updatedTime);
        $dueTicket = \DB::select('select tickets.id, tickets.assigned_to, tickets.dept_id,  users.first_name from tickets join users on tickets.assigned_to = users.id where duedate < "'.$updatedTime.'"' );

        foreach ($dueTicket as $key) {
            
            $teamId = \DB::select('select team_id from team_assign_agent where agent_id = '.$key->assigned_to);
            
            foreach ($teamId as $ids) {
                $teamId = \DB::select('select team_id from team_assign_agent where  team_id >'. $ids->team_id.'  order by team_id asc limit 1');
               

                $agentId = \DB::select('select agent_id,users.email, users.first_name from team_assign_agent join users on team_assign_agent.agent_id = users.id  where users.primary_dpt='.$key->dept_id.' and team_id ='.$teamId[0]->team_id);

                $agnAgentId = $agentId[0]->agent_id;
                $email =  $agentId[0]->email;
                $newAgent = $agentId[0]->first_name;
                $timeToAdd = \DB::select('select trim(replace(grace_period, "Hours", "")) as addTime from helpdesk3.tickets join helpdesk3.sla_plan on tickets.sla=sla_plan.id  where tickets.id ='. $key->id);
                $timeToadd = $timeToAdd[0]->addTime;
                
                $timestamp = strtotime($updatedTime) + $timeToadd*60*60;
                $updatedTimes = date("Y-m-d H:i:s", $timestamp);
                

                if (count($agentId)) {

                \DB::table('tickets')
                    ->where('id', $key->id)
                    ->update([
                        'duedate' => $updatedTimes,
                        'assigned_to' => $agnAgentId
                    ]);

                    \DB::table('ticket_assign_history')->insert([
                        'ticket_id' => $key->id,
                        'agent_id' => $key->assigned_to
                    ]);
                       
                    
                
                    $data = [
                        'email' => $email,
                        'ticket_id' => $key->id,
                        'new_agent' => $newAgent
                    ];

                    // $emails = ['myoneemail@esomething.com', 'myother@esomething.com','myother2@esomething.com'];


                    Mail::send('emails.mail', $data, function ($message) {
                        $message->from($email, 'Ticketing');
                        $message->to($email)->subject('Ticket # - '. $key->id. ' '.$teamId[0]->team_id. ' - Escalation!');;
                    });


                }            
            }
        }
    }
}
