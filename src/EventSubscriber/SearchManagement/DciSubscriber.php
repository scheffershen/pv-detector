<?php

namespace App\EventSubscriber\SearchManagement;

use App\Event\SearchManagement\DciEvent;
use App\Repository\SearchManagement\IndexationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DciSubscriber implements EventSubscriberInterface
{
    private $manager;
    private $indexationRepository;

    public function __construct(EntityManagerInterface $manager, IndexationRepository $indexationRepository)
    {
        $this->indexationRepository = $indexationRepository;
        $this->manager = $manager;        
    }

    public static function getSubscribedEvents()
    {
        return [
            DciEvent::DESINDEXER => 'desIndexser',
        ];
    }

    public function desIndexser(DciEvent $event)
    {
        $dci = $event->getDci();
        $indexs = $this->indexationRepository->findBy(['dci' => $dci]);
        foreach ($indexs as $index) {
            $this->manager->remove($index);
        }
        $this->manager->flush();

    }   
}