<?php
namespace App\Message;

class ExportProductsMessage 
{
    public function __construct(private string $recipientEmail)
    {
        
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }
}