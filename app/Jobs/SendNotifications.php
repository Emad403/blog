<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotifications implements ShouldQueue
{
    use Queueable;

    protected $users;
    protected $document;

    public function __construct($users, $document)
    {
        $this->users = $users;
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // we can implement message broker or queues for this in production in order to prevent failure and ...
        // also we can send email to our users after smtp setup or any other kind of notification model
        // if we used relational database for our user we could select and find the users in order to send the notification
        
        // foreach ($users as $userData) {
        //   $user = new User($userData['username'], $userData['email']);
        //   $user->notify(new NotifyUsers($document));
        // }
    }
}
