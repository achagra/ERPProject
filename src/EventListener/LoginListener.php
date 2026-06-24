<?php

namespace App\EventListener;

use App\Entity\Users;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class LoginListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof Users && !$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte n\'est pas encore vérifié. Consultez votre email.'
            );
        }
    }
}