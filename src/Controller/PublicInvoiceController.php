<?php

namespace App\Controller;

use App\Entity\CommandeAchat;
use App\Entity\CommandeVente;
use App\Repository\CommandeAchatRepository;
use App\Repository\CommandeVenteRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PublicInvoiceController extends AbstractController
{
    #[Route('/facture-public/{type}/{id}/{token}', name: 'public_invoice', methods: ['GET'], requirements: ['type' => 'achat|vente', 'id' => '\\d+', 'token' => '[a-f0-9]{64}'])]
    public function show(
        string $type,
        int $id,
        string $token,
        CommandeAchatRepository $achatRepository,
        CommandeVenteRepository $venteRepository
    ): Response {
        $this->denyUnlessValidToken($type, $id, $token);
        $commande = $this->findCommande($type, $id, $achatRepository, $venteRepository);

        if (!$commande) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        $invoiceUrl = $this->publicUrl($type, $id);

        return $this->render('dashbord/order_invoice.html.twig', [
            'commande' => $commande,
            'type' => $type,
            'invoiceUrl' => $invoiceUrl,
            'publicPdfUrl' => $this->publicUrl($type, $id, true),
            'qrCode' => $this->createQrCodeDataUri($invoiceUrl),
        ]);
    }

    #[Route('/facture-public/{type}/{id}/{token}/pdf', name: 'public_invoice_pdf', methods: ['GET'], requirements: ['type' => 'achat|vente', 'id' => '\\d+', 'token' => '[a-f0-9]{64}'])]
    public function download(
        string $type,
        int $id,
        string $token,
        CommandeAchatRepository $achatRepository,
        CommandeVenteRepository $venteRepository
    ): Response {
        $this->denyUnlessValidToken($type, $id, $token);
        $commande = $this->findCommande($type, $id, $achatRepository, $venteRepository);

        if (!$commande) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        $html = $this->renderView('dashbord/order_invoice_pdf.html.twig', [
            'commande' => $commande,
            'type' => $type,
            'qrCode' => $this->createQrCodeDataUri($this->publicUrl($type, $id)),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facture-' . $type . '-' . $id . '.pdf"',
        ]);
    }

    private function findCommande(
        string $type,
        int $id,
        CommandeAchatRepository $achatRepository,
        CommandeVenteRepository $venteRepository
    ): CommandeAchat|CommandeVente|null {
        return $type === 'achat'
            ? $achatRepository->find($id)
            : $venteRepository->find($id);
    }

    private function denyUnlessValidToken(string $type, int $id, string $token): void
    {
        $expected = hash_hmac('sha256', $type . ':' . $id, (string) $this->getParameter('kernel.secret'));

        if (!hash_equals($expected, $token)) {
            throw $this->createAccessDeniedException('Lien de facture invalide.');
        }
    }

    private function publicUrl(string $type, int $id, bool $pdf = false): string
    {
        $route = $pdf ? 'public_invoice_pdf' : 'public_invoice';
        $path = $this->generateUrl($route, [
            'type' => $type,
            'id' => $id,
            'token' => hash_hmac('sha256', $type . ':' . $id, (string) $this->getParameter('kernel.secret')),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
        $publicUrl = trim((string) $this->getParameter('app.public_url'));

        return $publicUrl === '' ? $this->generateUrl($route, [
            'type' => $type,
            'id' => $id,
            'token' => hash_hmac('sha256', $type . ':' . $id, (string) $this->getParameter('kernel.secret')),
        ], UrlGeneratorInterface::ABSOLUTE_URL) : rtrim($publicUrl, '/') . $path;
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
}
