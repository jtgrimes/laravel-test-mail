<?php namespace JTGrimes\TestMail;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Mail\Transport\Transport;
use Psr\Log\LoggerInterface;
use Swift_Mime_Message;
use Swift_Mime_MimeEntity;
use \Config;

class TestTransport extends Transport
{
    private $logger;
    private $filesystem;

    public function __construct(LoggerInterface $logger, Filesystem $filesystem)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }

    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->writeMessageToFile($this->getMimeEntityString($message));

        $this->logger->debug('Sent mail: '.$message->getSubject());

        return $this->numberOfRecipients($message);
    }

    protected function getMimeEntityString(Swift_Mime_MimeEntity $entity)
    {
        $string = (string) $entity->getHeaders().PHP_EOL.$entity->getBody();

        foreach ($entity->getChildren() as $children) {
            $string .= PHP_EOL.PHP_EOL.$this->getMimeEntityString($children);
        }

        return $string;
    }

    private function writeMessageToFile($messageText)
    {
        $messageText .= PHP_EOL."=============================================================================".PHP_EOL;
        $fileNameToWrite = Config::get('mail.debug_log');
        if (!$this->filesystem->exists($fileNameToWrite)) {
            $this->filesystem->put($fileNameToWrite, '');
        }
        $this->filesystem->append($fileNameToWrite, $messageText);
    }
}
