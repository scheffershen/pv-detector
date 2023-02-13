<?php

namespace App\Form\RevueManagement\EventSubscriber;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class AddReceiptDateFieldSubscriber implements EventSubscriberInterface
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SET_DATA => 'onReceiptDate'];
    }

    public function onReceiptDate(FormEvent $event): void
    {
        $form = $event->getForm();
        $request = $this->requestStack->getCurrentRequest();

        if ('fr' == $request->getLocale()) {
            $form->add('receiptDate', DateType::class, [
                    'required' => true,
                    'html5' => false,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'help' => 'dd/mm/yyyy',
                    'attr' => ['class' => 'pop-date'],
                    'label' => 'numero.receiptDate',
                ]);
        } else {
            $form->add('receiptDate', DateType::class, [
                    'required' => true,
                    'html5' => false,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'help' => 'yyyy-mm-dd',
                    'attr' => ['class' => 'pop-date'],
                    'label' => 'numero.receiptDate',
                ]);
        }

    }
}