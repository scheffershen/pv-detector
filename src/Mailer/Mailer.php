<?php

namespace App\Mailer;

use App\Entity\UserManagement\LoggedMessage;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class Mailer implements MailerInterface
{
    private $mailer;
    private $logger;
    private $doctrine;

    public function __construct(\Swift_Mailer $mailer, LoggerInterface $logger, ManagerRegistry $doctrine)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
    }

    public function sendMail(array $from, string $to, string $purpose, string $body, array $Cc = null): ?bool
    {
        $message = (new \Swift_Message($purpose))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html')
            ->setCharset('utf-8');

        if ($Cc) {
            $message->setCc($Cc);
        }

        try {
            $reponse = $this->mailer->send($message);
            $this->log($message);

            return true;
        } catch (\Swift_TransportException $e) {
            $this->logger->error($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }

    public function log($message, $result = 1, $failures = [])
    {
        $loggedMessage = new LoggedMessage();
        $loggedMessage->setMessage($message);
        $loggedMessage->setResult($result);
        $loggedMessage->setFailedRecipients($failures);

        $em = $this->doctrine->getManagerForClass(LoggedMessage::class);

        try {
            $em->persist($loggedMessage);
            $em->flush($loggedMessage);
        } catch (\Exception $e) {
            $error = 'Logging sent message with \SwiftmailerLoggerBundle\Logger\EntityLogger failed: '.
                $e->getMessage();
            $this->logger->error($error);
        }
    }
}
