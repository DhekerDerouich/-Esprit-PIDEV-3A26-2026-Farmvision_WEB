<?php
// src/Service/InvoiceService.php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Twig\Environment;

class InvoiceService
{
    private ?Pdf $pdf;
    private Environment $twig;
    private EntityManagerInterface $em;
    private string $invoiceDir;

    public function __construct(?Pdf $pdf = null, Environment $twig = null, EntityManagerInterface $em = null, string $invoiceDir = '')
    {
        $this->pdf = $pdf;
        $this->twig = $twig;
        $this->em = $em;
        $this->invoiceDir = $invoiceDir;
    }

    /**
     * Generate invoice from a real Stripe session.
     */
    public function generateInvoice(Cart $cart, object $stripeSession, string $customerEmail, ?string $customerName = null): Invoice
    {
        return $this->buildInvoice($cart, $stripeSession->id, $customerEmail, $customerName);
    }

    /**
     * Generate invoice without Stripe (simulation / demo mode).
     */
    public function generateInvoiceSimulated(Cart $cart, string $fakeSessionId, string $customerEmail, ?string $customerName = null): Invoice
    {
        return $this->buildInvoice($cart, $fakeSessionId, $customerEmail, $customerName);
    }

    private function buildInvoice(Cart $cart, string $sessionId, string $customerEmail, ?string $customerName): Invoice
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
        $invoice->setStripeSessionId($sessionId);
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

        // PDF generation — optional, skipped if wkhtmltopdf not installed
        try {
            if ($this->pdf !== null) {
                $html    = $this->twig->render('front/pdf/invoice.html.twig', [
                    'invoice' => $invoice,
                    'items'   => $items,
                    'date'    => new \DateTime(),
                ]);
                $pdfPath = $this->invoiceDir . '/' . $invoiceNumber . '.pdf';
                $this->pdf->generateFromHtml($html, $pdfPath);
                $invoice->setPdfPath($pdfPath);
                $this->em->flush();
            }
        } catch (\Exception $e) {
            // wkhtmltopdf not installed or failed — continue without PDF
        }

        return $invoice;
    }
}
