<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Users;
use App\Form\EmployeType;
use App\Form\UsersType;
use App\Repository\EmployeRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashbord')]
#[IsGranted('ROLE_ADMIN')]
class DashbordController extends AbstractController
{
    #[Route('/', name: 'dashbord_index', methods: ['GET'])]
    public function index(EmployeRepository $employeRepo): Response
{
    if ($this->isGranted('ROLE_SUPER_ADMIN')) {
        return $this->redirectToRoute('dashbord_users');
    }

    /** @var Users $admin */
    $admin = $this->getUser();
    $employes = $employeRepo->findByCreatedBy($admin);

    return $this->render('dashbord/entreprise.html.twig', [
        'totalEmployes' => count($employes),
        'employes'      => array_slice($employes, 0, 5),
    ]);
}

    // ================= USERS (SUPER_ADMIN uniquement) =================
    #[Route('/users', name: 'dashbord_users', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function manageUsers(UsersRepository $repo): Response
    {
        return $this->render('dashbord/users.html.twig', [
            'users'      => $repo->findAll(),
            'totalUsers' => count($repo->findAll()),
        ]);
    }

    #[Route('/users/new', name: 'dashbord_user_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
public function newUser(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): Response {
    $user = new Users();

    $form = $this->createForm(UsersType::class, $user, [
        'is_edit' => false,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $plainPassword = $form->get('password')->getData();
        if ($plainPassword) {
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        }

        $user->setRole($form->get('role')->getData());
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur créé avec succès.');
        return $this->redirectToRoute('dashbord_users');
    }

    return $this->render('dashbord/user_form.html.twig', [
        'form'  => $form,
        'user'  => $user,
        'title' => 'Ajouter un utilisateur',
    ]);
}

    #[Route('/users/{id}/edit', name: 'dashbord_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function editUser(
        Request $request,
        Users $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UsersType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            // Met à jour le rôle si modifié
            $role = $form->get('role')->getData();
            if ($role) {
                $user->setRole($role);
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('dashbord_users');
        }

        return $this->render('dashbord/user_form.html.twig', [
            'form'  => $form,
            'user'  => $user,
            'title' => 'Modifier ' . $user->getNom() . ' ' . $user->getPrenom(),
        ]);
    }

    #[Route('/users/{id}/delete', name: 'dashbord_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function deleteUser(Request $request, Users $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            if ($user->getId() === $this->getUser()?->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('dashbord_users');
            }

            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_users');
    }

    #[Route('/users/{id}', name: 'dashbord_user_show', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function showUser(Users $user): Response
    {
        return $this->render('dashbord/user_show.html.twig', [
            'user' => $user,
        ]);
    }

    // ================= EMPLOYES (ADMIN uniquement — isolés par createdBy) =================
    #[Route('/employes', name: 'dashbord_employes', methods: ['GET'])]
    public function manageEmployes(EmployeRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();

        return $this->render('dashbord/employes.html.twig', [
            'employes' => $repo->findByCreatedBy($admin),
        ]);
    }

    #[Route('/employes/new', name: 'dashbord_employe_new', methods: ['GET', 'POST'])]
    public function newEmploye(Request $request, EntityManagerInterface $em): Response
    {
        $employe = new Employe();

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $employe->setCreatedBy($admin);

            $em->persist($employe);
            $em->flush();

            $this->addFlash('success', 'Employé ajouté avec succès.');
            return $this->redirectToRoute('dashbord_employes');
        }

        return $this->render('dashbord/employe_form.html.twig', [
            'form'  => $form,
            'title' => 'Ajouter un employé',
        ]);
    }

    #[Route('/employes/{id}/edit', name: 'dashbord_employe_edit', methods: ['GET', 'POST'])]
    public function editEmploye(Request $request, Employe $employe, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessOwner($employe);

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Employé modifié avec succès.');
            return $this->redirectToRoute('dashbord_employes');
        }

        return $this->render('dashbord/employe_form.html.twig', [
            'form'  => $form,
            'title' => 'Modifier ' . $employe->getNom() . ' ' . $employe->getPrenom(),
        ]);
    }

    #[Route('/employes/{id}/delete', name: 'dashbord_employe_delete', methods: ['POST'])]
    public function deleteEmploye(Request $request, Employe $employe, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessOwner($employe);

        if ($this->isCsrfTokenValid('delete_employe' . $employe->getId(), $request->request->get('_token'))) {
            $em->remove($employe);
            $em->flush();

            $this->addFlash('success', 'Employé supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_employes');
    }

    private function denyAccessUnlessOwner(Employe $employe): void
    {
        /** @var Users $current */
        $current = $this->getUser();

        if ($employe->getCreatedBy()?->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cet employé.");
        }
    }
}