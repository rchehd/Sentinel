<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ChangePasswordRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/api/password/change', name: 'api_password_change', methods: ['POST'])]
    public function change(
        #[CurrentUser] User $user,
        #[MapRequestPayload] ChangePasswordRequest $dto,
    ): JsonResponse {
        if ($dto->newPassword !== $dto->confirmPassword) {
            return $this->json(
                ['code' => 'password_mismatch', 'error' => 'Passwords do not match.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (!$user->isMustChangePassword()) {
            if ($dto->currentPassword === null) {
                return $this->json(
                    ['code' => 'current_password_required', 'error' => 'Current password is required.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
                return $this->json(
                    ['code' => 'invalid_current_password', 'error' => 'Current password is incorrect.'],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->newPassword));
        $user->setMustChangePassword(false);
        $this->em->flush();

        return $this->json(['message' => 'Password changed successfully.']);
    }
}
