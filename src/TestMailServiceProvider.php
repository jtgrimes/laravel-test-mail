<?php namespace JTGrimes\TestMail;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Mail\MailServiceProvider;
use Psr\Log\LoggerInterface;

class TestMailServiceProvider extends MailServiceProvider
{
    public function registerSwiftMailer()
    {
        if ($this->app['config']['mail.driver'] == 'test') {
            $this->registerTestSwiftMailer();
        } else {
            parent::registerSwiftMailer();
        }
    }

    private function registerTestSwiftMailer()
    {
        $this->app['swift.mailer'] = $this->app->share(function ($app) {
            return new \Swift_Mailer(
                new TestTransport(
                    $app->make(LoggerInterface::class),
                    $app->make(Filesystem::class)
                )
            );
        });
    }
}
