<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Entreprise;
use App\Entity\Employe;
use App\Entity\Fournisseur;
use App\Entity\Projet;
use App\Entity\Users;
use App\Form\ClientType;
use App\Form\EntrepriseType;
use App\Form\EmployeType;
use App\Form\FournisseurType;
use App\Form\ProjetType;
use App\Form\UsersType;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\EmployeRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProjetRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function index(
        EmployeRepository $employeRepo,
        ClientRepository $clientRepo,
        FournisseurRepository $fournisseurRepo
    ): Response {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('dashbord_users');
        }

        /** @var Users $admin */
        $admin = $this->getUser();
        $employes = $employeRepo->findByCreatedBy($admin);
        $clients = $clientRepo->findBy(['createdBy' => $admin]);
        $fournisseurs = $fournisseurRepo->findBy(['createdBy' => $admin]);

        $totalClientsParticuliers = count(array_filter($clients, static fn (Client $client): bool => $client->getType() === 'particulier'));
        $totalClientsProfessionnels = count(array_filter($clients, static fn (Client $client): bool => $client->getType() === 'entreprise'));
        $totalFournisseursParticuliers = count(array_filter($fournisseurs, static fn (Fournisseur $fournisseur): bool => $fournisseur->getType() === 'particulier'));
        $totalFournisseursProfessionnels = count(array_filter($fournisseurs, static fn (Fournisseur $fournisseur): bool => $fournisseur->getType() === 'entreprise'));

        return $this->render('dashbord/entreprise.html.twig', [
            'totalEmployes' => count($employes),
            'totalClientsParticuliers' => $totalClientsParticuliers,
            'totalClientsProfessionnels' => $totalClientsProfessionnels,
            'totalFournisseursParticuliers' => $totalFournisseursParticuliers,
            'totalFournisseursProfessionnels' => $totalFournisseursProfessionnels,
        ]);
    }

    #[Route('/entreprise/profil', name: 'dashbord_entreprise_profile', methods: ['GET', 'POST'])]
    public function profileEntreprise(
        Request $request,
        EntityManagerInterface $em,
        EntrepriseRepository $entrepriseRepository
    ): Response {
        /** @var Users $admin */
        $admin = $this->getUser();

        $entreprise = $entrepriseRepository->findOneBy(['createdBy' => $admin]);
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setCreatedBy($admin);
        }

        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile instanceof UploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/entreprises';
                $newFilename = uniqid('entreprise_', true) . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move($uploadDir, $newFilename);
                    $entreprise->setPhotoFilename($newFilename);
                } catch (FileException $exception) {
                    $this->addFlash('danger', 'Impossible de téléverser la photo de l\'entreprise.');
                }
            }

            $em->persist($entreprise);
            $em->flush();

            $this->addFlash('success', 'Profil de l\'entreprise enregistré avec succès.');

            return $this->redirectToRoute('dashbord_entreprise_profile');
        }

        return $this->render('dashbord/entreprise_profile.html.twig', [
            'form' => $form,
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/projets', name: 'dashbord_projets', methods: ['GET'])]
    public function manageProjets(ProjetRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();
        $projets = $repo->findBy(['createdBy' => $admin], ['id' => 'DESC']);

        $totalProjets = count($projets);
        $totalActifs = count(array_filter($projets, static fn (Projet $projet): bool => $projet->isActive()));
        $totalInactifs = $totalProjets - $totalActifs;

        return $this->render('dashbord/projets.html.twig', [
            'projets' => $projets,
            'totalProjets' => $totalProjets,
            'totalActifs' => $totalActifs,
            'totalInactifs' => $totalInactifs,
        ]);
    }

    #[Route('/projets/new', name: 'dashbord_projet_new', methods: ['GET', 'POST'])]
    public function newProjet(Request $request, EntityManagerInterface $em): Response
    {
        $projet = new Projet();
        $projet->setIsActive(true);

        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $projet->setCreatedBy($admin);

            $em->persist($projet);
            $em->flush();

            $this->addFlash('success', 'Projet ajouté avec succès.');

            return $this->redirectToRoute('dashbord_projets');
        }

        return $this->render('dashbord/projet_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un projet',
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
            $currentUser = $this->getUser();

            if ($currentUser instanceof Users && $user->getId() === $currentUser->getId()) {
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
        $this->denyAccessUnlessEmployeOwner($employe);

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
        $this->denyAccessUnlessEmployeOwner($employe);

        if ($this->isCsrfTokenValid('delete_employe' . $employe->getId(), $request->request->get('_token'))) {
            $em->remove($employe);
            $em->flush();

            $this->addFlash('success', 'Employé supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_employes');
    }

    #[Route('/clients', name: 'dashbord_clients', methods: ['GET'])]
    public function manageClients(ClientRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();
        $clients = $repo->findBy(['createdBy' => $admin], ['id' => 'DESC']);

        $totalParticuliers = count(array_filter($clients, static fn (Client $client): bool => $client->getType() === 'particulier'));
        $totalProfessionnels = count(array_filter($clients, static fn (Client $client): bool => $client->getType() === 'entreprise'));

        return $this->render('dashbord/clients.html.twig', [
            'clients' => $clients,
            'totalParticuliers' => $totalParticuliers,
            'totalProfessionnels' => $totalProfessionnels,
        ]);
    }

    #[Route('/clients/new', name: 'dashbord_client_new', methods: ['GET', 'POST'])]
    public function newClient(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $client->setCreatedBy($admin);

            $em->persist($client);
            $em->flush();

            $this->addFlash('success', 'Client ajouté avec succès.');
            return $this->redirectToRoute('dashbord_clients');
        }

        return $this->render('dashbord/client_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un client',
        ]);
    }

    #[Route('/clients/{id}/edit', name: 'dashbord_client_edit', methods: ['GET', 'POST'])]
    public function editClient(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessClientOwner($client);

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Client modifié avec succès.');
            return $this->redirectToRoute('dashbord_clients');
        }

        return $this->render('dashbord/client_form.html.twig', [
            'form' => $form,
            'title' => 'Modifier ' . $client->getNomComplet(),
        ]);
    }

    #[Route('/clients/{id}/delete', name: 'dashbord_client_delete', methods: ['POST'])]
    public function deleteClient(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessClientOwner($client);

        if ($this->isCsrfTokenValid('delete_client' . $client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();

            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_clients');
    }

    #[Route('/fournisseurs', name: 'dashbord_fournisseurs', methods: ['GET'])]
    public function manageFournisseurs(FournisseurRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();
        $fournisseurs = $repo->findBy(['createdBy' => $admin], ['id' => 'DESC']);

        $totalParticuliers = count(array_filter($fournisseurs, static fn (Fournisseur $fournisseur): bool => $fournisseur->getType() === 'particulier'));
        $totalProfessionnels = count(array_filter($fournisseurs, static fn (Fournisseur $fournisseur): bool => $fournisseur->getType() === 'entreprise'));

        return $this->render('dashbord/fournisseurs.html.twig', [
            'fournisseurs' => $fournisseurs,
            'totalParticuliers' => $totalParticuliers,
            'totalProfessionnels' => $totalProfessionnels,
        ]);
    }

    #[Route('/fournisseurs/new', name: 'dashbord_fournisseur_new', methods: ['GET', 'POST'])]
    public function newFournisseur(Request $request, EntityManagerInterface $em): Response
    {
        $fournisseur = new Fournisseur();

        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $fournisseur->setCreatedBy($admin);

            $em->persist($fournisseur);
            $em->flush();

            $this->addFlash('success', 'Fournisseur ajouté avec succès.');
            return $this->redirectToRoute('dashbord_fournisseurs');
        }

        return $this->render('dashbord/fournisseur_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un fournisseur',
        ]);
    }

    #[Route('/fournisseurs/{id}/edit', name: 'dashbord_fournisseur_edit', methods: ['GET', 'POST'])]
    public function editFournisseur(Request $request, Fournisseur $fournisseur, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessFournisseurOwner($fournisseur);

        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Fournisseur modifié avec succès.');
            return $this->redirectToRoute('dashbord_fournisseurs');
        }

        return $this->render('dashbord/fournisseur_form.html.twig', [
            'form' => $form,
            'title' => 'Modifier ' . $fournisseur->getNomComplet(),
        ]);
    }

    #[Route('/fournisseurs/{id}/delete', name: 'dashbord_fournisseur_delete', methods: ['POST'])]
    public function deleteFournisseur(Request $request, Fournisseur $fournisseur, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessFournisseurOwner($fournisseur);

        if ($this->isCsrfTokenValid('delete_fournisseur' . $fournisseur->getId(), $request->request->get('_token'))) {
            $em->remove($fournisseur);
            $em->flush();

            $this->addFlash('success', 'Fournisseur supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_fournisseurs');
    }

    private function denyAccessUnlessEmployeOwner(Employe $employe): void
    {
        /** @var Users $current */
        $current = $this->getUser();

        if ($employe->getCreatedBy()?->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cet employé.");
        }
    }

    private function denyAccessUnlessClientOwner(Client $client): void
    {
        /** @var Users $current */
        $current = $this->getUser();

        if ($client->getCreatedBy()?->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce client.");
        }
    }

    private function denyAccessUnlessFournisseurOwner(Fournisseur $fournisseur): void
    {
        /** @var Users $current */
        $current = $this->getUser();

        if ($fournisseur->getCreatedBy()?->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce fournisseur.");
        }
    }
}