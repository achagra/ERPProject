<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Client;
use App\Entity\CommandeAchat;
use App\Entity\CommandeVente;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use App\Entity\Entrepot;
use App\Entity\Entreprise;
use App\Entity\Employe;
use App\Entity\Fournisseur;
use App\Entity\Product;
use App\Entity\Projet;
use App\Entity\Users;
use App\Form\CategoryType;
use App\Form\ClientType;
use App\Form\CommandeAchatType;
use App\Form\CommandeVenteType;
use App\Form\EntrepotType;
use App\Form\EntrepriseType;
use App\Form\EmployeType;
use App\Form\FournisseurType;
use App\Form\ProductType;
use App\Form\ProjetType;
use App\Form\UsersType;
use App\Repository\CategoryRepository;
use App\Repository\ClientRepository;
use App\Repository\CommandeAchatRepository;
use App\Repository\CommandeVenteRepository;
use App\Repository\EntrepotRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\EmployeRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProductRepository;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashbord')]
#[IsGranted('ROLE_ADMIN')]
class DashbordController extends AbstractController
{
    #[Route('/', name: 'dashbord_index', methods: ['GET'])]
    public function index(
        Request $request,
        EmployeRepository $employeRepo,
        ClientRepository $clientRepo,
        FournisseurRepository $fournisseurRepo,
        CommandeAchatRepository $achatRepo,
        CommandeVenteRepository $venteRepo
    ): Response {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('dashbord_users');
        }

        /** @var Users $admin */
        $admin = $this->getUser();
        $employes = $employeRepo->findByCreatedBy($admin);
        $clients = $clientRepo->findBy(['createdBy' => $admin]);
        $fournisseurs = $fournisseurRepo->findBy(['createdBy' => $admin]);
        $period = in_array($request->query->getInt('period', 90), [30, 90, 365], true) ? $request->query->getInt('period', 90) : 90;
        $from = new \DateTimeImmutable(sprintf('-%d days', $period));
        $achats = array_filter($achatRepo->findByCreatedBy($admin), static fn (CommandeAchat $commande): bool => $commande->getDateFacture() >= $from);
        $ventes = array_filter($venteRepo->findByCreatedBy($admin), static fn (CommandeVente $commande): bool => $commande->getDateCommande() >= $from);
        $months = [];
        $monthCursor = new \DateTimeImmutable('first day of this month');
        for ($index = 5; $index >= 0; --$index) {
            $month = $monthCursor->modify(sprintf('-%d months', $index));
            $months[$month->format('Y-m')] = $month->format('m/Y');
        }
        $achatChart = array_fill_keys(array_keys($months), 0);
        $venteChart = array_fill_keys(array_keys($months), 0);
        foreach ($achats as $commande) {
            $key = $commande->getDateFacture()?->format('Y-m');
            if (isset($achatChart[$key])) { ++$achatChart[$key]; }
        }
        foreach ($ventes as $commande) {
            $key = $commande->getDateCommande()?->format('Y-m');
            if (isset($venteChart[$key])) { ++$venteChart[$key]; }
        }

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
            'period' => $period,
            'periodAchats' => count($achats),
            'periodVentes' => count($ventes),
            'chartLabels' => array_values($months),
            'chartAchats' => array_values($achatChart),
            'chartVentes' => array_values($venteChart),
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

    #[Route('/entrepots', name: 'dashbord_entrepots', methods: ['GET'])]
    public function manageEntrepots(EntrepotRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();
        $entrepots = $repo->findByCreatedBy($admin);

        $totalEntrepots = count($entrepots);
        $totalActifs = count(array_filter($entrepots, static fn (Entrepot $entrepot): bool => $entrepot->isActive()));
        $totalInactifs = $totalEntrepots - $totalActifs;

        return $this->render('dashbord/entrepots.html.twig', [
            'entrepots' => $entrepots,
            'totalEntrepots' => $totalEntrepots,
            'totalActifs' => $totalActifs,
            'totalInactifs' => $totalInactifs,
        ]);
    }

    #[Route('/entrepots/new', name: 'dashbord_entrepot_new', methods: ['GET', 'POST'])]
    public function newEntrepot(Request $request, EntityManagerInterface $em): Response
    {
        $entrepot = new Entrepot();
        $entrepot->setIsActive(true);

        $form = $this->createForm(EntrepotType::class, $entrepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $entrepot->setCreatedBy($admin);

            $em->persist($entrepot);
            $em->flush();

            $this->addFlash('success', 'Entrepôt ajouté avec succès.');

            return $this->redirectToRoute('dashbord_entrepots');
        }

        return $this->render('dashbord/entrepot_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un entrepôt',
        ]);
    }

    #[Route('/ventes', name: 'dashbord_ventes', methods: ['GET'])]
    public function manageVentes(CommandeVenteRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();

        return $this->render('dashbord/ventes.html.twig', [
            'commandes' => $repo->findByCreatedBy($admin),
        ]);
    }

    #[Route('/ventes/new', name: 'dashbord_vente_new', methods: ['GET', 'POST'])]
    public function newVente(Request $request, EntityManagerInterface $em): Response
    {
        $commande = new CommandeVente();
        $commande->setDateCommande(new \DateTimeImmutable());

        $form = $this->createForm(CommandeVenteType::class, $commande, ['owner' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $commande->setCreatedBy($admin);

            $em->persist($commande);
            $em->flush();

            $this->addFlash('success', 'Commande vente ajoutée avec succès.');

            return $this->redirectToRoute('dashbord_ventes');
        }

        return $this->render('dashbord/ventes_form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle commande client',
        ]);
    }

    #[Route('/achats', name: 'dashbord_achats', methods: ['GET'])]
    public function manageAchats(CommandeAchatRepository $repo): Response
    {
        /** @var Users $admin */
        $admin = $this->getUser();

        return $this->render('dashbord/achats.html.twig', [
            'commandes' => $repo->findByCreatedBy($admin),
        ]);
    }

    #[Route('/achats/new', name: 'dashbord_achat_new', methods: ['GET', 'POST'])]
    public function newAchat(Request $request, EntityManagerInterface $em): Response
    {
        $commande = new CommandeAchat();
        $commande->setDateFacture(new \DateTimeImmutable());

        $form = $this->createForm(CommandeAchatType::class, $commande, ['owner' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Users $admin */
            $admin = $this->getUser();
            $commande->setCreatedBy($admin);

            $em->persist($commande);
            $em->flush();

            $this->addFlash('success', 'Commande achat ajoutée avec succès.');

            return $this->redirectToRoute('dashbord_achats');
        }

        return $this->render('dashbord/achats_form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle commande fournisseur',
        ]);
    }

    #[Route('/achats/{id}/facture', name: 'dashbord_achat_facture', methods: ['GET'])]
    public function showAchatInvoice(CommandeAchat $commande): Response
    {
        $this->denyAccessUnlessCommandeOwner($commande->getCreatedBy());

        $invoiceUrl = $this->generatePublicInvoiceUrl('achat', $commande->getId());

        return $this->render('dashbord/order_invoice.html.twig', [
            'commande' => $commande,
            'type' => 'achat',
            'invoiceUrl' => $invoiceUrl,
            'publicPdfUrl' => $this->generatePublicInvoiceUrl('achat', $commande->getId(), true),
            'qrCode' => $this->createQrCodeDataUri($invoiceUrl),
        ]);
    }

    #[Route('/achats/{id}/facture/pdf', name: 'dashbord_achat_facture_pdf', methods: ['GET'])]
    public function downloadAchatInvoice(CommandeAchat $commande): Response
    {
        $this->denyAccessUnlessCommandeOwner($commande->getCreatedBy());

        return $this->downloadOrderInvoice(
            $commande,
            'achat',
            'facture-achat-' . $commande->getId() . '.pdf'
        );
    }

    #[Route('/ventes/{id}/facture', name: 'dashbord_vente_facture', methods: ['GET'])]
    public function showVenteInvoice(CommandeVente $commande): Response
    {
        $this->denyAccessUnlessCommandeOwner($commande->getCreatedBy());

        $invoiceUrl = $this->generatePublicInvoiceUrl('vente', $commande->getId());

        return $this->render('dashbord/order_invoice.html.twig', [
            'commande' => $commande,
            'type' => 'vente',
            'invoiceUrl' => $invoiceUrl,
            'publicPdfUrl' => $this->generatePublicInvoiceUrl('vente', $commande->getId(), true),
            'qrCode' => $this->createQrCodeDataUri($invoiceUrl),
        ]);
    }

    #[Route('/ventes/{id}/facture/pdf', name: 'dashbord_vente_facture_pdf', methods: ['GET'])]
    public function downloadVenteInvoice(CommandeVente $commande): Response
    {
        $this->denyAccessUnlessCommandeOwner($commande->getCreatedBy());

        return $this->downloadOrderInvoice(
            $commande,
            'vente',
            'facture-vente-' . $commande->getId() . '.pdf'
        );
    }

    #[Route('/entrepots/{id}/edit', name: 'dashbord_entrepot_edit', methods: ['GET', 'POST'])]
    public function editEntrepot(Request $request, Entrepot $entrepot, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessEntrepotOwner($entrepot);

        $form = $this->createForm(EntrepotType::class, $entrepot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Entrepôt modifié avec succès.');

            return $this->redirectToRoute('dashbord_entrepots');
        }

        return $this->render('dashbord/entrepot_form.html.twig', [
            'form' => $form,
            'title' => 'Modifier ' . $entrepot->getNom(),
        ]);
    }

    #[Route('/entrepots/{id}/delete', name: 'dashbord_entrepot_delete', methods: ['POST'])]
    public function deleteEntrepot(Request $request, Entrepot $entrepot, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessEntrepotOwner($entrepot);

        if ($this->isCsrfTokenValid('delete_entrepot' . $entrepot->getId(), $request->request->get('_token'))) {
            $em->remove($entrepot);
            $em->flush();

            $this->addFlash('success', 'Entrepôt supprimé avec succès.');
        }

        return $this->redirectToRoute('dashbord_entrepots');
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
    public function manageUsers(UsersRepository $repo, EntrepriseRepository $entrepriseRepository): Response
    {
        return $this->render('dashbord/users.html.twig', [
            'users'      => $repo->findAll(),
            'totalUsers' => count($repo->findAll()),
            'totalEntreprises' => $entrepriseRepository->count([]),
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

    #[Route('/produits', name: 'dashbord_produits', methods: ['GET', 'POST'])]
    public function manageProduits(Request $request, ProductRepository $repo, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $products = $repo->findBy([], ['id' => 'DESC']);
        $totalMatieresPremieres = count(array_filter($products, static fn (Product $product): bool => $product->getAssetType() === 'matiere_premiere'));
        $totalComposites = count(array_filter($products, static fn (Product $product): bool => $product->getAssetType() === 'composite_assemblage'));
        $totalServices = count(array_filter($products, static fn (Product $product): bool => $product->getAssetType() === 'service'));

        $category = new Category();
        $categoryForm = $this->createForm(CategoryType::class, $category);
        $categoryForm->handleRequest($request);

        if ($categoryForm->isSubmitted() && $categoryForm->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Catégorie ajoutée avec succès.');

            return $this->redirectToRoute('dashbord_produits');
        }

        return $this->render('dashbord/products.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'categoryForm' => $categoryForm->createView(),
            'totalProducts' => count($products),
            'totalMatieresPremieres' => $totalMatieresPremieres,
            'totalComposites' => $totalComposites,
            'totalServices' => $totalServices,
        ]);
    }

    #[Route('/produits/new', name: 'dashbord_produit_new', methods: ['GET', 'POST'])]
    public function newProduit(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();

        $form = $this->createForm(ProductType::class, $product, ['owner' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté avec succès.');

            return $this->redirectToRoute('dashbord_produits');
        }

        return $this->render('dashbord/product_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter un produit ou service',
        ]);
    }

    #[Route('/produits/{id}/facture', name: 'dashbord_produit_facture', methods: ['GET'])]
    public function showInvoice(Product $product, EntrepriseRepository $entrepriseRepository): Response
    {
        $entreprise = $entrepriseRepository->findOneBy(['createdBy' => $this->getUser()]);

        return $this->render('dashbord/invoice.html.twig', [
            'product' => $product,
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/produits/{id}/facture/pdf', name: 'dashbord_produit_facture_pdf', methods: ['GET'])]
    public function downloadInvoice(Product $product, EntrepriseRepository $entrepriseRepository): Response
    {
        $entreprise = $entrepriseRepository->findOneBy(['createdBy' => $this->getUser()]);

        $html = $this->renderView('dashbord/invoice_pdf.html.twig', [
            'product' => $product,
            'entreprise' => $entreprise,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $qrCode = new QrCode(
            data: $this->generateInvoiceUrl('dashbord_produit_facture', $product->getId()),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 160,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new SvgWriter();
        $qrImage = $writer->write($qrCode)->getString();
        $qrPath = sys_get_temp_dir() . '/invoice-' . $product->getId() . '.svg';
        file_put_contents($qrPath, $qrImage);

        $output = $dompdf->output();
        $filename = 'facture-' . $product->getId() . '.pdf';

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function downloadOrderInvoice(
        CommandeAchat|CommandeVente $commande,
        string $type,
        string $filename
    ): Response {
        $invoiceUrl = $this->generatePublicInvoiceUrl($type, $commande->getId());
        $qrCode = $this->createQrCodeDataUri($invoiceUrl);
        $html = $this->renderView('dashbord/order_invoice_pdf.html.twig', [
            'commande' => $commande,
            'type' => $type,
            'qrCode' => $qrCode,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function createQrCodeDataUri(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 180,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $result = (new SvgWriter())->write($qrCode);

        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    private function generatePublicInvoiceUrl(string $type, ?int $id, bool $pdf = false): string
    {
        $route = $pdf ? 'public_invoice_pdf' : 'public_invoice';
        $path = $this->generateUrl($route, [
            'type' => $type,
            'id' => $id,
            'token' => $this->createInvoiceToken($type, $id),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
        $publicUrl = trim((string) $this->getParameter('app.public_url'));

        return $publicUrl === '' ? $this->generateUrl($route, [
            'type' => $type,
            'id' => $id,
            'token' => $this->createInvoiceToken($type, $id),
        ], UrlGeneratorInterface::ABSOLUTE_URL) : rtrim($publicUrl, '/') . $path;
    }

    private function createInvoiceToken(string $type, ?int $id): string
    {
        return hash_hmac('sha256', $type . ':' . $id, (string) $this->getParameter('kernel.secret'));
    }

    private function denyAccessUnlessCommandeOwner(?Users $owner): void
    {
        /** @var Users|null $current */
        $current = $this->getUser();

        if (!$current instanceof Users || !$owner instanceof Users || $owner->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette commande.");
        }
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

    private function denyAccessUnlessEntrepotOwner(Entrepot $entrepot): void
    {
        /** @var Users $current */
        $current = $this->getUser();

        if ($entrepot->getCreatedBy()?->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cet entrepôt.");
        }
    }
}