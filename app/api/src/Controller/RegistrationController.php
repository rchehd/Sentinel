<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationRequest;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMember;
use App\Enum\UserRole;
use App\Enum\WorkspaceRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(default::FRONTEND_URL)%')]
        private readonly string $frontendUrl = 'https://sentinel.localhost',
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegistrationRequest $dto): JsonResponse
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setRoles([UserRole::User->value]);
        $user->setIsActive(false);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $activationToken = bin2hex(random_bytes(32));
        $user->setActivationToken($activationToken);

        $workspace = new Workspace();
        $workspace->setName(\sprintf("%s's workspace", $dto->username));

        $member = new WorkspaceMember();
        $member->setUser($user);
        $member->setRole(WorkspaceRole::Owner);
        $workspace->addMember($member);

        $this->em->persist($user);
        $this->em->persist($workspace);
        $this->em->flush();

        $this->sendActivationEmail($user, $activationToken);

        return $this->json(
            ['message' => 'Registration successful. Please check your email to activate your account.'],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/api/activate/{token}', name: 'api_activate', methods: ['GET'])]
    public function activate(string $token): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['activationToken' => $token]);

        if (null === $user) {
            return $this->json(
                ['error' => 'Invalid activation token.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        if ($user->isActive()) {
            return $this->json(
                ['code' => 'already_activated', 'error' => 'Account is already activated.'],
                Response::HTTP_CONFLICT,
            );
        }

        $user->setIsActive(true);
        $this->em->flush();

        return $this->json(['message' => 'Account activated successfully.']);
    }

    private function sendActivationEmail(User $user, string $token): void
    {
        $activationUrl = \sprintf('%s/activate/%s', rtrim($this->frontendUrl, '/'), $token);

        $email = (new Email())
            ->from('noreply@sentinel.localhost')
            ->to($user->getEmail() ?? '')
            ->subject('Activate your Sentinel account')
            ->html(\sprintf(
                '<h1>Welcome to Sentinel!</h1>'
                . '<p>Hi %s,</p>'
                . '<p>Please click the link below to activate your account:</p>'
                . '<p><a href="%s">Activate my account</a></p>'
                . '<p>If you did not register, please ignore this email.</p>',
                $user->getFirstName() ?? $user->getUsername(),
                $activationUrl,
            ));

        $this->mailer->send($email);
    }
}
