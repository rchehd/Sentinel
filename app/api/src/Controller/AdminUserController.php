<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AdminCreateUserRequest;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json($users, context: ['groups' => ['user:read']]);
    }

    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] AdminCreateUserRequest $dto): JsonResponse
    {
        if (null !== $this->userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(
                ['errors' => ['email' => 'This email is already in use.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (null !== $this->userRepository->findOneBy(['username' => $dto->username])) {
            return $this->json(
                ['errors' => ['username' => 'This username is already taken.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setRoles([UserRole::User->value]);
        $user->setIsActive(true);
        $user->setMustChangePassword($dto->mustChangePassword);

        $generatedPassword = null;
        $plainPassword = $dto->password;

        if (null === $plainPassword) {
            $generatedPassword = bin2hex(random_bytes(8));
            $plainPassword = $generatedPassword;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        $response = [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'mustChangePassword' => $user->isMustChangePassword(),
        ];

        if (null !== $generatedPassword) {
            $response['generatedPassword'] = $generatedPassword;
        }

        return $this->json($response, Response::HTTP_CREATED);
    }
}
