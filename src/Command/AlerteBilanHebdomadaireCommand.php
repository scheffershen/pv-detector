<?php

namespace App\Command;

use App\Constants;
use App\Entity\UserManagement\User;
use App\Entity\UserManagement\Client;
use App\Entity\UserManagement\Plateforme;
use App\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AlerteBilanHebdomadaireCommand extends Command
{
    protected static $defaultName = 'app:alerte-bilan-hebdomadaire';
    protected static $defaultDescription = 'Add a short description for your command';

    private $entityManager;
    private $mailer;
    private $twig;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, Environment $twig, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add a short description for your command')
            ->setHelp('This command allows you to alert the user...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $begin = new \DateTime();   
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' ---</comment>');
        
        $plateforme = $this->entityManager->getRepository(Plateforme::class)->findOneBy(['id' => Constants::PLATEFORME_ID]);

        $clients = $this->entityManager->getRepository(Client::class)->findByClientsDuJour();
        $output->writeln('<comment>Nombre de clients: ' . \count($clients) . ' ---</comment>');
        
        if (\count($clients) > 0 ) {
            $users = $this->entityManager->getRepository(User::class)->findByRespondableClient($clients);
            if (\count($users) > 0 ) {
                foreach ($users as $user) {
                    $output->writeln('<comment>Nombre de utilisateurs: ' . \count($users) . ' ---</comment>');
                    $this->mailer->sendMail([$plateforme->getEmail()], $user->getEmail(), $this->translator->trans('client.flash.bilan_hebdomadaire'), $this->twig->render('UserManagement/emails/bilan_hebdomadaire_notification.html.twig', [
                            'user' => $user,
                            'clients' => $clients
                        ])
                    );
                }
            }
        }

        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');
        
        return Command::SUCCESS;
    }
}
