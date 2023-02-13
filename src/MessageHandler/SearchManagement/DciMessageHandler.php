<?php

namespace App\MessageHandler\SearchManagement;

use App\Entity\SearchManagement\Dci;
use App\Entity\SearchManagement\Indexation;
use App\Message\SearchManagement\DciMessage;
use App\Repository\RevueManagement\NumeroRepository;
use App\Repository\SearchManagement\IndexationRepository;
use App\Utils\WordSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DciMessageHandler implements MessageHandlerInterface
{
    private SessionInterface $session;
    private IndexationRepository $indexationRepository;
    private NumeroRepository $numeroRepository;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, IndexationRepository $indexationRepository, NumeroRepository $numeroRepository, TranslatorInterface $translator)
    {
        $this->indexationRepository = $indexationRepository;
        $this->numeroRepository = $numeroRepository;	
        $this->entityManager = $entityManager;	
        $this->session = $session;
        $this->translator = $translator;
    }

    public function __invoke(DciMessage $dciMessage)
    {
        $dci = $dciMessage->getDci();

        $numeros = $this->numeroRepository->findBy(['isValid' => true, 'state' => 'treatment'], ['updateDate' => 'ASC']);

        $this->removeOldIndexs($dci);

        $total = 0;
        $indexed = 0;
        $failed = 0;

        foreach ($numeros as $numero) {
            foreach ($numero->getImages() as $image) {
                if (!$image->isDeleted()) {
                    foreach ($image->getPages() as $page) {
                        if (!$page->isDeleted()) {
                            if ( WordSearch::find_word($page->getBlocksText(), $dci->getTitle())) {     
                                $indexation = new Indexation();
                                $indexation->setNumero($numero);
                                $indexation->setImage($image);
                                $indexation->setPage($page);
                                $indexation->setDci($dci);
                                $nb_occurrence = WordSearch::count_exact_words($page->getBlocksText(), $dci->getTitle());
                                $nb_occurrence = ($nb_occurrence > 0)?$nb_occurrence:1;
                                $indexation->setOccurrence($nb_occurrence); 

                                try {
                                    $this->entityManager->persist($indexation);
                                    $this->entityManager->flush();
                                    ++$indexed;
                                } catch (\Exception $ex) {
                                    $this->session->getFlashBag()->add('error', sprintf('Failed indexing: %s', $ex->getMessage() ) );
                                    ++$failed;
                                }

                            }
                        }
                    }
                }
            }
        }

        $dci->setIsIndexed(true);
        $this->entityManager->persist($dci);
        $this->entityManager->flush();

        if ($failed > 0) {
            $this->session->getFlashBag()->add('warning', sprintf('Number of error: %s', $failed));
        }

        if ($indexed > 0) {
            $this->session->getFlashBag()->add('success', sprintf('Number of indexed: %s', $indexed));
        }

    }

    /**
     * Remove old indexations and create new ones
     *
     * @param Dci $dci
     * @return void
     */
    private function removeOldIndexs(Dci $dci): void
    {
        $indexs = $this->indexationRepository->findBy(['dci' => $dci]);
        foreach ($indexs as $index) {
            $this->entityManager->remove($index);
        }
        $this->entityManager->flush();
    }
}
