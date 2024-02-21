<?php

namespace App\Service\Auth;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailService
{
    public function __construct(/*private readonly MailerInterface $mailer*/)
    {
    }

    public function sendInformationChanged(): void
    {
        /*
        $user = $event->getUser();
        $email = (new TemplatedEmail())
            ->from(new Address('registration@mystation-service.com', 'Orbis Exploitation'))
            ->to($user->getEmail())
            ->subject('Recover password')
            ->htmlTemplate('emails/forget_password.html.twig')
            ->context([
                'user' => $user,
            ]);
        $this->mailer->send($email);
        */
    }

    public function sendNewUserInformation(User $user): void
    {
        /*
        $user = $event->getUser();
        $plainPassword = $event->getPlainPassword();
        $email = (new TemplatedEmail())
            ->from(new Address('registration@mystation-service.com', 'Orbis Exploitation'))
            ->to($user->getEmail())
            ->subject('Registration Account')
            ->htmlTemplate('emails/registration.html.twig')
            ->context([
                'expiration_date' => new \DateTime('+7 days'),
                'user' => $user,
                'plain_password' => $plainPassword
            ]);
        $this->mailer->send($email);
        */
    }

}
