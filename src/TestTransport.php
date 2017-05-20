<?php namespace JTGrimes\TestMail;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Mail\Transport\Transport;
use Psr\Log\LoggerInterface;
use Swift_Mime_Message;
use \Config;

class TestTransport extends Transport
{
    private $logger;
    private $filesystem;
    private $previewPath;
    private $lifetime;

    public function __construct(LoggerInterface $logger, Filesystem $filesystem, $previewPath, $lifetime = 60)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->previewPath = $previewPath;
        $this->lifetime = $lifetime;
    }

    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->createEmailPreviewDirectory();

        $this->cleanOldPreviews();

        $previewPath = $this->getPreviewFilePath($message);

        $this->writeFiles($previewPath, $message);
        $this->logger->debug('Sent mail: '.$message->getSubject());

        return $this->numberOfRecipients($message);
    }

    private function writeFiles($previewPath, $message)
    {
        $this->filesystem->put(
            $previewPath.'.html',
            $this->getHTMLPreviewContent($message)
        );

        $this->filesystem->put(
            $previewPath.'.eml',
            $this->getEMLPreviewContent($message)
        );
    }

    /**
     * Get the path to the email preview file.
     *
     * @param  \Swift_Mime_Message $message
     *
     * @return string
     */
    protected function getPreviewFilePath(Swift_Mime_Message $message)
    {
        $to = str_replace(['@', '.'], ['_at_', '_'], array_keys($message->getTo())[0]);
        $subject = $message->getSubject();
        return $this->previewPath.'/'.str_slug($message->getDate().'_'.$to.'_'.$subject, '_');
    }
    /**
     * Get the HTML content for the preview file.
     *
     * @param  \Swift_Mime_Message $message
     *
     * @return string
     */
    protected function getHTMLPreviewContent(Swift_Mime_Message $message)
    {
        $messageInfo = $this->getMessageInfo($message);
        return $messageInfo.$message->getBody();
    }
    /**
     * Get the EML content for the preview file.
     *
     * @param  \Swift_Mime_Message $message
     *
     * @return string
     */
    protected function getEMLPreviewContent(Swift_Mime_Message $message)
    {
        return $message->toString();
    }
    /**
     * Generate a human readable HTML comment with message info.
     *
     * @param \Swift_Mime_Message $message
     *
     * @return string
     */
    private function getMessageInfo(Swift_Mime_Message $message)
    {
        return sprintf(
            "<!--\nFrom:%s, \nto:%s, \nreply-to:%s, \ncc:%s, \nbcc:%s, \nsubject:%s\n-->\n",
            json_encode($message->getFrom()),
            json_encode($message->getTo()),
            json_encode($message->getReplyTo()),
            json_encode($message->getCc()),
            json_encode($message->getBcc()),
            $message->getSubject()
        );
    }
    /**
     * Create the preview directory if necessary.
     *
     * @return void
     */
    protected function createEmailPreviewDirectory()
    {
        if (! $this->filesystem->exists($this->previewPath)) {
            $this->filesystem->makeDirectory($this->previewPath);
            $this->filesystem->put($this->previewPath.'/.gitignore', "*\n!.gitignore");
        }
    }
    /**
     * Delete previews older than the given life time configuration.
     *
     * @return void
     */
    private function cleanOldPreviews()
    {
        $oldPreviews = array_filter($this->filesystem->files($this->previewPath), function ($file) {
            return time() - $this->filesystem->lastModified($file) > $this->lifetime;
        });
        if ($oldPreviews) {
            $this->filesystem->delete($oldPreviews);
        }
    }
}
