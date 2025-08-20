<?php
namespace App\MessageHandler;

use App\Message\ExportProductsMessage;
use App\Service\ProductExporter;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler()]
class ExportProductsMessageHandler 
{
    public function __construct(
        private ProductExporter $productExporter,
        private MailerInterface $mailer
    ) {
    }

    public function __invoke(ExportProductsMessage $message)
    {
        $csvContent = $this->productExporter->exportToCsv();

        $email = (new Email())
            ->from('noreply@yourdomain.com')
            ->to($message->getRecipientEmail())
            ->subject('Export de vos produits')
            ->text('Voici le fichier contenant vos produits')
            ->attach($csvContent, 'export.csv', 'text/csv');
        $this->mailer->send($email);
    }
}