<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\SetupAdminDto;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        #[Autowire('%env(APP_MODE)%')]
        private readonly string $appMode,
    ) {
    }

    #[Route('/api/setup/status', name: 'api_setup_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'configured' => $this->userRepository->count() > 0,
            'mode' => $this->appMode,
        ]);
    }

    #[Route('/api/setup/admin', name: 'api_setup_admin', methods: ['POST'])]
    public function createAdmin(#[MapRequestPayload] SetupAdminDto $dto): JsonResponse
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setRoles([UserRole::SuperAdmin->value]);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['message' => 'Admin account created.'], Response::HTTP_CREATED);
    }
}
