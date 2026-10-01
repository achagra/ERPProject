<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashbord/audit')]
#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    #[Route('', name: 'dashbord_audit', methods: ['GET'])]
    public function index(Request $request, AuditLogRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Users || $user->getRole() === 'ROLE_SUPER_ADMIN') {
            throw $this->createAccessDeniedException();
        }

        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));
        $action = $request->query->get('action');
        $actorId = $request->query->getInt('actor') ?: null;

        return $this->render('dashbord/audit.html.twig', [
            'logs' => $repository->searchForOwner($user, $action, $actorId, $from, $to),
            'filters' => [
                'action' => $action,
                'actor' => $actorId,
                'from' => $request->query->get('from'),
                'to' => $request->query->get('to'),
            ],
        ]);
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}