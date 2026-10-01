<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\Users;
use App\Form\EmployeType;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe')]
#[IsGranted('ROLE_ADMIN')]
class EmployeController extends AbstractController
{
    #[Route('/', name: 'employe_index', methods: ['GET'])]
    public function index(EmployeRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('employe/index.html.twig', [
            'employes' => $repo->findByCreatedBy($user),
        ]);
    }

    #[Route('/new', name: 'employe_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $employe = new Employe();
        $form    = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employe->setCreatedBy($this->getUser()); // ← lier à l'admin connecté
            $em->persist($employe);
            $em->flush();

            $this->addFlash('success', 'Employé ajouté avec succès.');
            return $this->redirectToRoute('employe_index');
        }

        return $this->render('employe/form.html.twig', [
            'form'  => $form,
            'title' => 'Ajouter un employé',
        ]);
    }

    #[Route('/{id}/edit', name: 'employe_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Employe $employe,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('TENANT_ACCESS', $employe);

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Employé modifié avec succès.');
            return $this->redirectToRoute('employe_index');
        }

        return $this->render('employe/form.html.twig', [
            'form'  => $form,
            'title' => 'Modifier ' . $employe->getNom() . ' ' . $employe->getPrenom(),
        ]);
    }

    #[Route('/{id}/delete', name: 'employe_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Employe $employe,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('TENANT_ACCESS', $employe);

        if ($this->isCsrfTokenValid('delete' . $employe->getId(), $request->request->get('_token'))) {
            $em->remove($employe);
            $em->flush();
            $this->addFlash('success', 'Employé supprimé.');
        }

        return $this->redirectToRoute('employe_index');
    }

    #[Route('/{id}', name: 'employe_show', methods: ['GET'])]
    public function show(Employe $employe): Response
    {
        $this->denyAccessUnlessGranted('TENANT_ACCESS', $employe);

        return $this->render('employe/show.html.twig', [
            'employe' => $employe,
        ]);
    }
}