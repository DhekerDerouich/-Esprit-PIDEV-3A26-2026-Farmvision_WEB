<?php
// src/Service/InvoiceService.php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Stripe\Checkout\Session;
use Twig\Environment;

class InvoiceService
{
    private Pdf $pdf;
    private Environment $twig;
    private EntityManagerInterface $em;
    private string $invoiceDir;

    public function __construct(Pdf $pdf, Environment $twig, EntityManagerInterface $em, string $invoiceDir)
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
        $this->em = $em;
        $this->invoiceDir = $invoiceDir;
    }

    public function generateInvoice(Cart $cart, Session $stripeSession, string $customerEmail, ?string $customerName = null): Invoice
    {
        if (!is_dir($this->invoiceDir)) {
            mkdir($this->invoiceDir, 0777, true);
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . uniqid();

        $invoice = new Invoice();
        $invoice->setInvoiceNumber($invoiceNumber);
        $invoice->setAmount($cart->getTotal());
        $invoice->setCurrency('EUR');
        $invoice->setCustomerEmail($customerEmail);
        $invoice->setCustomerName($customerName);
        $invoice->setStripeSessionId($stripeSession->id);
        $invoice->setStatus('paid');

        $items = [];
        foreach ($cart->getItems() as $item) {
            $items[] = [
                'product_name' => $item->getMarketplace()->getStock()->getNomProduit(),
                'quantity'     => $item->getQuantity(),
                'price'        => $item->getPrice(),
                'total'        => $item->getTotal(),
            ];
        }
        $invoice->setItems(json_encode($items));

        $this->em->persist($invoice);
        $this->em->flush();

        // Generate PDF
        try {
            $html = $this->twig->render('front/pdf/invoice.html.twig', [
                'invoice' => $invoice,
                'items'   => $items,
                'date'    => new \DateTime(),
            ]);

            $pdfPath = $this->invoiceDir . '/' . $invoiceNumber . '.pdf';
            $this->pdf->generateFromHtml($html, $pdfPath);
            $invoice->setPdfPath($pdfPath);
            $this->em->flush();
        } catch (\Exception $e) {
            // PDF generation failed (e.g. wkhtmltopdf not installed) — continue without PDF
        }

        return $invoice;
    }
}
