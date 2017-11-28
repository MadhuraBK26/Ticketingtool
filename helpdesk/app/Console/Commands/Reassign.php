<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;

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
