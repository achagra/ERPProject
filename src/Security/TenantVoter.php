<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TenantVoter extends Voter
{
    public const ACCESS = 'TENANT_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS
            && is_object($subject)
            && method_exists($subject, 'getCreatedBy');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $owner = $subject->getCreatedBy();

        return $user instanceof Users
            && $owner instanceof Users
            && $user->getId() === $owner->getId()
            && $user->getRole() !== 'ROLE_SUPER_ADMIN';
    }
}
