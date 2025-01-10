<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;

class MailjetService
{
    private Client $mailjetClient;
    private string $senderEmail;

    public function __construct(string $apiKeyPublic, string $apiKeyPrivate, string $senderEmail)
    {
        $this->mailjetClient = new Client($apiKeyPublic, $apiKeyPrivate, true, ['version' => 'v3.1']);
        $this->senderEmail = $senderEmail;
    }

    public function sendEmail(string $recipientEmail, string $recipientName, string $subject, string $textPart, string $htmlPart): bool
    {
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->senderEmail,
                        'Name' => 'Electro-Multifix'
                    ],
                    'To' => [
                        [
                            'Email' => $recipientEmail,
                            'Name' => $recipientName
                        ]
                    ],
                    'Subject' => $subject,
                    'TextPart' => $textPart,
                    'HTMLPart' => $htmlPart,
                ]
            ]
        ];

        $response = $this->mailjetClient->post(Resources::$Email, ['body' => $body]);

        return $response->success();
    }
}
