<?php namespace JTGrimes\TestMail;

use Mail;
use Swift_Message;

// this is pulled from https://gist.github.com/JeffreyWay/b501c53d958b07b8a332
// Thanks, Jeff Way.
trait MailTracking
{
    /**
     * Delivered emails.
     */
    protected $emails = [];

    /**
     * Register a listener for new emails.
     * Reminder: the "before" tag below tells PHPUnit to run this method before each test if this
     * trait is used.
     *
     * @before
     */
    public function setUpMailTracking()
    {
        Mail::getSwiftMailer()
            ->registerPlugin(new TestingMailEventListener($this));
    }

    /**
     * Assert that at least one email was sent.
     */
    protected function seeEmailWasSent()
    {
        $this->assertNotEmpty($this->emails, 'No emails have been sent.');
        return $this;
    }

    /**
     * Assert that no emails were sent.
     */
    protected function seeEmailWasNotSent()
    {
        $this->assertEmpty($this->emails, 'Did not expect any emails to have been sent.');
        return $this;
    }

    /**
     * Assert that the given number of emails were sent.
     *
     * @param integer $count
     * @return $this
     */
    protected function seeEmailsSent($count)
    {
        $emailsSent = count($this->emails);
        $this->assertCount(
            $count,
            $this->emails,
            "Expected $count emails to have been sent, but $emailsSent were."
        );
        return $this;
    }

    /**
     * Assert that the last email's body equals the given text.
     *
     * @param string $body
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailEquals($body, Swift_Message $message = null)
    {
        $this->assertEquals(
            $body,
            $this->getEmail($message)->getBody(),
            "No email with the provided body was sent."
        );
        return $this;
    }

    /**
     * Assert that the last email's body contains the given text.
     *
     * @param string $excerpt
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailContains($excerpt, Swift_Message $message = null)
    {
        $this->assertContains(
            $excerpt,
            $this->getEmail($message)->getBody(),
            "No email containing the provided body was found."
        );
        return $this;
    }


    /**
     * Assert that the last email's body does not contain the given text.
     *
     * @param string $excerpt
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailNotContains($excerpt, Swift_Message $message = null)
    {
        $this->assertNotContains(
            $excerpt,
            $this->getEmail($message)->getBody(),
            "The text was found in the email"
        );
        return $this;
    }


    /**
     * Assert that the last email's subject matches the given string.
     *
     * @param string $subject
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailSubjectContains($subject, Swift_Message $message = null)
    {
        $this->assertContains(
            $subject,
            $this->getEmail($message)->getSubject(),
            "No email with a subject of $subject was found."
        );
        return $this;
    }

    /**
     * Assert that the last email's subject matches the given string.
     *
     * @param string $subject
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailSubjectNotContains($subject, Swift_Message $message = null)
    {
        $this->assertNotContains(
            $subject,
            $this->getEmail($message)->getSubject(),
            "The email subject does contain '$subject'."
        );
        return $this;
    }

    /**
     * Assert that any email's subject matches the given string.
     *
     * @param string $subject
     * @return $this
     */
    protected function seeAnyEmailSubjectContains($subject)
    {
        $found = false;
        for ($i = 0; $i < count($this->emails); $i++) {
            if (strpos($this->emails[$i]->getSubject(), $subject) !== false) {
                $found = true;
            }
        }
        $this->assertEquals($found, true, "No email with a subject of $subject was found.");

        return $this;
    }

    /**
     * Assert that the last email was sent to the given recipient.
     *
     * @param string $recipient
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailTo($recipient, Swift_Message $message = null)
    {
        $this->assertArrayHasKey(
            $recipient,
            (array) $this->getEmail($message)->getTo(),
            "No email was sent to $recipient."
        );
        return $this;
    }

    /**
     * Assert that the specified number of emails have been sent to the specified recipient
     *
     * @param string $recipient
     * @return $this
     */
    protected function seeEmailsTo($recipient, $count)
    {
        $sent = 0;
        foreach ($this->emails as $email) {
            if (array_key_exists($recipient, $email->getTo())) {
                $sent++;
            }
        }
        $this->assertEquals($count, $sent);
        return $this;
    }

    /**
     * Assert that the last email was delivered by the given address.
     *
     * @param string $sender
     * @param Swift_Message $message
     * @return $this
     */
    protected function seeEmailFrom($sender, Swift_Message $message = null)
    {
        $this->assertArrayHasKey(
            $sender,
            (array) $this->getEmail($message)->getFrom(),
            "No email was sent from $sender."
        );
        return $this;
    }

    /**
     * Store a new swift message.
     *
     * @param Swift_Message $email
     */
    public function addEmail(Swift_Message $email)
    {
        $this->emails[] = $email;
    }

    /**
     * Retrieve the appropriate swift message.
     *
     * @param Swift_Message $message
     * @return mixed
     */
    protected function getEmail(Swift_Message $message = null)
    {
        $this->seeEmailWasSent();
        return $message ?: $this->lastEmail();
    }
    /**
     * Retrieve the mostly recently sent swift message.
     */
    protected function lastEmail()
    {
        return end($this->emails);
    }

    /**
     * Get the first email with the given subject
     *
     * @param string $subject
     * @return $this
     */
    public function getEmailContainingSubject($subject)
    {
        $found = -1;
        for ($i = 0; $i < count($this->emails); $i++) {
            if (strpos($this->emails[$i]->getSubject(), $subject) !== false) {
                $found = $i;
            }
        }
        if ($found < 0) {
            $this->assertTrue(false, "Could not find email with subject $subject");
        }

        return $this->emails[$found];
    }

    /**
     * Get the first email to a given recipient
     * @param $recipient
     * @return mixed
     */
    public function getEmailTo($recipient)
    {
        $found = -1;
        for ($i = 0; $i < count($this->emails); $i++) {
            $recipients = (array) $this->emails[$i]->getTo();
            if (array_key_exists($recipient, $recipients)) {
                $found = $i;
            }
        }
        if ($found < 0) {
            $this->assertTrue(false, "Could not find email to $recipient");
        }

        return $this->emails[$found];
    }
}
