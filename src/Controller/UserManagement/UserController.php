<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\User;
use App\Form\UserManagement\UserEditType;
use App\Form\UserManagement\UserDeleteType;
use App\Form\UserManagement\UserType;
use App\Repository\UserManagement\UserRepository;
use App\Service\UserManagement\UserService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapterEvents;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

/**
 * @Route("/admin/user")
 */
class UserController extends AbstractController
{
    private $parameter;
    private $translator;

    public function __construct(ParameterBagInterface $parameter, TranslatorInterface $translator)
    {
        $this->parameter = $parameter;
        $this->translator = $translator;
    }

    /**
     * @Route("/index", name="admin_users_index", methods={"GET"})
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('UserManagement/User/index.html.twig', [
             'users' => $userRepository->findBy(['isDeleted' => false], ['updateDate' => 'DESC']),
         ]);
    }

    /**
     * @Route("/new", name="admin_user_new")
     *
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function new(Request $request, UserService $service): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($user->getRoles()) ) {
                //$form->addError(new FormError($this->translator->trans('user.error.missing_role')));
                $this->addFlash('error', $this->translator->trans('user.error.missing_role'));  
            } elseif (\in_array('ROLE_CLIENT', $user->getRoles(), true) && $user->getClients()->isEmpty() ) {    
                //$form->addError(new FormError($this->translator->trans('user.error.missing_client'))); 
                $this->addFlash('error', $this->translator->trans('user.error.missing_client'));   
            } else {
                $user->setCreateUser($this->getUser());
                $user->setUpdateUser($this->getUser());

                try {
                    $service->create($user);
                    
                    if ($user->getId())
                        return $this->redirectToRoute('admin_user_show', ['id'=>$user->getId()]);   
                } catch (\Throwable $exception) {
                    $this->addFlash('error', $exception->getMessage());
                }

            } 
        }

        return $this->render('UserManagement/User/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            //'roles' => USER::ROLES,
        ]);
    }

    /**
     * @Route("/show/{id}", name="admin_user_show", methods={"GET"})
     * 
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function show(Request $request, User $user): Response
    {
        $form = $this->createForm(UserDeleteType::class, $user);

        return $this->render('UserManagement/User/show.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'action' => $request->get('action'),
        ]);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/edit/{id}",methods={"GET", "POST"}, name="admin_user_edit")
     *
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function edit(Request $request, User $user, UserService $service, SluggerInterface $slugger, KernelInterface $kernel): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($user->getRoles()) ) {
                //$form->addError(new FormError($this->translator->trans('user.error.missing_role')));  
                $this->addFlash('error', $this->translator->trans('user.error.missing_role')); 
            } elseif (\in_array('ROLE_CLIENT', $user->getRoles(), true) && $user->getClients()->isEmpty() ) {    
                //$form->addError(new FormError($this->translator->trans('user.error.missing_client'))); 
                $this->addFlash('error', $this->translator->trans('user.error.missing_role'));  
            } else {
                $user->setUpdateUser($this->getUser());
                try {
                    $service->update($user);
                    
                    return $this->redirectToRoute('admin_user_show', ['id'=>$user->getId()]);  
                } catch (\Throwable $exception) {
                    $this->addFlash('error', $exception->getMessage());
                }
            } 
        }

        return $this->render('UserManagement/User/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'roles' => USER::ROLES,
        ]);
    }

    /**
     * Desactive/Active un user.
     *
     * @Route("/disable/{id}", name="admin_user_disable", methods={"POST"})
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function disable(User $user)
    {
        $em = $this->getDoctrine()->getManager();

        if ($user->getIsEnable()) {
            $user->setIsEnable(false);
            $this->addFlash('success', $this->translator->trans('user.flash.disable'));
        } else {
            $user->setIsEnable(true);
            $this->addFlash('success', $this->translator->trans('user.flash.enable'));
        }

        try {
            $user->setUpdateUser($this->getUser());
            $em->persist($user);
            $em->flush();
        } catch (\Doctrine\DBAL\DBALException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_user_show', ['id'=>$user->getId()]);
    }

    /**
     * Deletes an User entity.
     *
     * @Route("/delete/{id}", methods={"POST"}, name="admin_user_delete")
     *
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function delete(Request $request, User $user): Response
    {
        $form = $this->createForm(UserDeleteType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $user->setUpdateUser($this->getUser());     
                $user->setIsDeleted(true);                           
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('user.flash.deleted'));
                return $this->redirectToRoute('admin_user_show', ['id'=>$user->getId()]);

            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('admin_user_show', ['id'=>$user->getId(), 'action'=>'delete']);
    }

    /**
     * @Route("/omines", name="admin_users_omines", methods={"GET", "POST"})
     * datatables Omines, useful, when your database are explosed
     * @Security("is_granted('ROLE_GESTION_UTILISATEURS')")
     */
    public function omines(Request $request, DataTableFactory $dataTableFactory, Environment $twig): Response
    {
        if (!$twig->hasExtension(StringLoaderExtension::class)) {
            $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        }

        $table = $dataTableFactory->create()
            ->add('actions', TwigStringColumn::class, [
                'template' => '<div class="btn-group" role="group"><a href="{{ url(\'admin_user_edit\', {id: row.id}) }}" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" data-original-title="{{ \'action.edit\' | trans }}"><i class="fas fa-pencil-alt"></i></a>&nbsp;<a href="{{ url(\'admin_user_show\', {id: row.id, \'delete\':1}) }}" class="btn btn-sm btn-danger" data-toggle="tooltip" data-original-title="{{ \'action.delete\' | trans }}"><i class="fas fa-trash"></i></a>&nbsp;<a href="{{ url(\'admin_audit_show_entity_history\', {\'entity\': \'App-Entity-UserManagement-User\', id: row.id}) }}" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" data-original-title="{{ \'action.audit\' | trans }}"><i class="fas fa-code-branch"></i></a></div>',
            ])
            ->add('fullname', TextColumn::class)
            ->add('firstname', TextColumn::class)
            ->add('lastname', TextColumn::class)
            ->add('email', TextColumn::class);

        $table->createAdapter(ORMAdapter::class, [
                'entity' => User::class,
                'hydrate' => Query::HYDRATE_ARRAY,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('u')
                        //->addSelect('s')
                        ->from(User::class, 'u')
                        //->leftJoin('u.gender', 's')
                        //->where('u.roles LIKE :role')
                        //->andWhere('u.isDeleted = false')
                        //->setParameter('role', '%"' . $role . '"%')
                        ->orderBy('u.updateDate', 'DESC');
                },
            ])
            ->handleRequest($request);

        $table->addEventListener(ORMAdapterEvents::PRE_QUERY, function (ORMAdapterQueryEvent $event) {
            $event->getQuery()->useResultCache(true)->useQueryCache(true);
        });

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('UserManagement/User/index.html.twig', ['datatable' => $table]);
    }
}
