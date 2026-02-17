<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'code' => 'invalid_credentials',
                'error' => 'Invalid credentials.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return $this->json([
                'code' => 'account_not_activated',
                'error' => 'Account is not activated. Please check your email.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ]);
    }
}
