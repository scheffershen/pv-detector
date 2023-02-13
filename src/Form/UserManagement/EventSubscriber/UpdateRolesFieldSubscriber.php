<?php

namespace App\Form\UserManagement\EventSubscriber;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UpdateRolesFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SUBMIT => 'onRolesChanged'];
    }

    public function onRolesChanged(FormEvent $event): void
    {
        $form = $event->getForm();

        if ($form->getData()) {
            $roles = $form->getData();
            if (\in_array('ROLE_CLIENT', $roles)) {
                $form->getParent()->add('clients', EntityType::class, [
                    'label' => 'user.client',
                    'class' => 'App\Entity\UserManagement\Client',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where('c.isValid = true')
                            ->orderBy('c.name', 'ASC');
                    },
                    'attr' => ['class' => 'js-select2'],
                    'multiple' => true,
                    'required' => true,
                ])                     
                ; 
            }
        }
    }
}
