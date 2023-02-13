<?php

namespace App\Notification\RevueManagement;

use App\Entity\RevueManagement\Numero;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class NumeroNotification extends Notification implements EmailNotificationInterface
{
    private $numero;
    private $action;

    public function __construct(Numero $numero, string $action)
    {
        $this->numero = $numero;
        $this->action = $action;

        parent::__construct();
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('RevueManagement/emails/numero_notification.html.twig')
            ->context(['numero' => $this->numero, 'action' => $this->action])
        ;

        return $message;
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }
}
