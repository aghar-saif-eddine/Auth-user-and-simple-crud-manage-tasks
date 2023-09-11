<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;


class UserController extends AbstractController
{
    private $em;
    private $user;
    private $jwtManager;
    private $passwordHasher;


    public function __construct(EntityManagerInterface $em, UserRepository $user, JWTTokenManagerInterface $jwtManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->user = $user;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;

    }

    #[Route('/register', name: 'register_user')]
    public function register(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $email = $data["email"];
        $password = $data["password"];
        $checkEmail = $this->user->findOneByEmail($email);

        if ($checkEmail) {
            return new JsonResponse(["status" => 500, "message" => "Cet email existe déjà, vous pouvez choisir un autre !"]);
        } else {
            $user = new User();
            $user->setUsername($email);
            $user->setEmail($email);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setStatus(true);
            $user->setRoles(["ROLE_USER"]);
            $user->setCreatedAt(new \DateTimeImmutable());
            // Generate a JWT token for the registered user
            $token = $this->jwtManager->create($user);

            $user->setToken($token);
            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse(['token' => $token, "status" => 200, "message" => "L’utilisateur a été créé avec succès !"], JsonResponse::HTTP_CREATED);

        }
    }

    #[Route('/login', name: 'login_user')]
    public function login(Request $request, Security $security): JsonResponse
    {
      $data = json_decode($request->getContent(), true);
        $email = $data['email'];

        // Find the user by email
        $user = $this->user->findOneByEmail($email);
        if (!$user) {
            return new JsonResponse(['message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        // Generate a JWT token for the authenticated user
        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token], JsonResponse::HTTP_OK);
    }
}
