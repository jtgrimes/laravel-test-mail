<?php namespace JTGrimes\TestMail;

use Swift_Events_EventListener;

class TestingMailEventListener implements Swift_Events_EventListener
{
    protected $test;

    /**
     * TestingMailEventListener constructor.
     * @param $test mixed will be the test class that is using the MailTracking trait
     */
    public function __construct($test)
    {
        $this->test = $test;
    }

    /**
     * @param $event
     */
    public function beforeSendPerformed($event)
    {
        $this->test->addEmail($event->getMessage());
    }
}
