<?php

namespace App\Command;

use App\Constants;
use App\Entity\RevueManagement\Revue;
use App\Entity\UserManagement\Plateforme;
use App\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AlerteFinAbonnementRevueCommand extends Command
{
    protected static $defaultName = 'app:alerte-fin-abonnement-revue';
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

        $revues = $this->entityManager->getRepository(Revue::class)->findByAbonnementEnd30Days();

        if ($plateforme instanceof Plateforme && \count($revues) > 0) {
            $output->writeln('<comment>Nombre de revue à la fin d\'abonnement: ' . \count($revues) . ' ---</comment>');
            $this->mailer->sendMail([$plateforme->getEmail()], $plateforme->getEmail(), $this->translator->trans('revue.flash.fin_abonnement'), $this->twig->render('RevueManagement/emails/revue_fin_abonnement_notification.html.twig', [
                    'revues' => $revues,
                ])
            );

        }

        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');
        
        return Command::SUCCESS;
    }
}
