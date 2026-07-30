<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Presentation\Http;

use App\Identity\Application\Command\RecordFailedLogin\RecordFailedLoginHandler;
use App\Identity\Application\Command\RevokeRefreshToken\RevokeRefreshTokenHandler;
use App\Identity\Application\Command\StoreRefreshToken\StoreRefreshTokenHandler;
use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaBackupCodeRepositoryInterface;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Infrastructure\Security\SymfonyUserProvider;
use App\Identity\Infrastructure\Security\TotpService;
use App\Identity\Presentation\Http\JwtApiController;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests for JwtApiController.
 * Uses real instances of all final classes to avoid PHPUnit final class mocking limitation.
 */
final class JwtApiControllerTest extends TestCase
{
    private const USER_ID   = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID   = '01BXZ4MDEKTSV4RRFFQ69G5ABC';
    private const USERNAME  = 'test.user';
    private const PASSWORD  = 'correct-horse-battery-staple';

    private function makeActiveUser(): User
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject(self::USERNAME),
            new HashedPasswordValueObject(password_hash(self::PASSWORD, PASSWORD_BCRYPT)),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        return $user;
    }

    /**
     * Build a real SymfonyUserProvider with mocked repositories.
     */
    private function makeUserProvider(User $user): SymfonyUserProvider
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn($user);

        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $permRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permRepo->method('findByIds')->willReturn([]);

        return new SymfonyUserProvider($userRepo, $roleRepo, $permRepo);
    }

    /**
     * @param RefreshTokenRepositoryInterface|null $refreshTokenRepo Optionally override for refresh tests
     */
    private function makeController(
        ?User $user = null,
        ?UserMfaConfig $mfaConfig = null,
        ?string $accessToken = 'jwt.access.token',
        ?RefreshTokenRepositoryInterface $refreshTokenRepo = null,
    ): JwtApiController {
        $user ??= $this->makeActiveUser();

        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->method('create')->willReturn($accessToken);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('isPasswordValid')->willReturnCallback(
            function ($u, string $pwd): bool {
                return $pwd === self::PASSWORD;
            },
        );

        $totp = new TotpService();

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $mfaRepo->method('findByUserId')->willReturn($mfaConfig);

        // Real handlers with mocked repositories
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01ARZ3NDEKTSV4RRFFQ69G5FB1');

        $storeHandler = new StoreRefreshTokenHandler(
            $this->createMock(RefreshTokenRepositoryInterface::class),
            $idGenerator,
        );

        $revokeHandler = new RevokeRefreshTokenHandler(
            $this->createMock(RefreshTokenRepositoryInterface::class),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $authLogIdGenerator = $this->createMock(IdGeneratorInterface::class);
        $authLogIdGenerator->method('generate')->willReturn('01ARZ3NDEKTSV4RRFFQ69G5FB2');

        $recordFailedLoginHandler = new RecordFailedLoginHandler(
            $userRepo,
            $this->createMock(AuthLogRepositoryInterface::class),
            $authLogIdGenerator,
        );

        if ($refreshTokenRepo === null) {
            $refreshTokenRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
            $refreshTokenRepo->method('findByHash')->willReturn(null);
        }

        // Real SymfonyUserProvider with mocked repos (not mockable, it's final)
        $userProvider = $this->makeUserProvider($user);

        $authLogRepo = $this->createMock(AuthLogRepositoryInterface::class);
        $authLogRepo->method('countRecentFailedByIp')->willReturn(0);

        $backupCodeRepo = $this->createMock(UserMfaBackupCodeRepositoryInterface::class);

        $controller = new JwtApiController(
            $jwtManager,
            $passwordHasher,
            $totp,
            $mfaRepo,
            $authLogRepo,
            $refreshTokenRepo,
            $storeHandler,
            $revokeHandler,
            $userProvider,
            $userRepo,
            $recordFailedLoginHandler,
            $backupCodeRepo,
        );

        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        return $controller;
    }

    public function testLoginReturnsTokensForValidCredentials(): void
    {
        $controller = $this->makeController();
        $request = new Request(content: json_encode([
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
        ]));

        /** @var JsonResponse $response */
        $response = $controller->login($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('access_token', $data['data']);
        self::assertArrayHasKey('refresh_token', $data['data']);
        self::assertArrayHasKey('token_type', $data['data']);
        self::assertSame('Bearer', $data['data']['token_type']);
        self::assertArrayHasKey('expires_in', $data['data']);
    }

    public function testLoginReturnsUnauthorizedForInvalidCredentials(): void
    {
        $controller = $this->makeController();
        $request = new Request(content: json_encode([
            'username' => self::USERNAME,
            'password' => 'wrong-password',
        ]));

        /** @var JsonResponse $response */
        $response = $controller->login($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
        self::assertSame('Credenciales inválidas.', $data['error']);
    }

    public function testLoginReturnsBadRequestWhenFieldsMissing(): void
    {
        $controller = $this->makeController();

        $request = new Request(content: json_encode([
            'username' => '',
            'password' => '',
        ]));

        /** @var JsonResponse $response */
        $response = $controller->login($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testLoginRequiresTotpWhenMfaEnabled(): void
    {
        $mfaConfig = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FB1'),
            new UserIdValueObject(self::USER_ID),
            'TESTSECRET',
        );
        $mfaConfig->confirm();

        $controller = $this->makeController(mfaConfig: $mfaConfig);
        $request = new Request(content: json_encode([
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
        ]));

        /** @var JsonResponse $response */
        $response = $controller->login($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('mfaRequired', $data);
        self::assertTrue($data['mfaRequired']);
    }

    public function testRefreshReturnsNewTokensForValidToken(): void
    {
        $user = $this->makeActiveUser();
        $plainToken = bin2hex(random_bytes(32));
        $refreshToken = RefreshToken::create(
            RefreshTokenIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB1'),
            new UserIdValueObject(self::USER_ID),
            $plainToken,
        );

        $refreshTokenRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepo->method('findByHash')->willReturn($refreshToken);

        $controller = $this->makeController(
            user: $user,
            refreshTokenRepo: $refreshTokenRepo,
        );

        $request = new Request(content: json_encode([
            'refresh_token' => $plainToken,
        ]));

        /** @var JsonResponse $response */
        $response = $controller->refresh($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('access_token', $data['data']);
        self::assertArrayHasKey('refresh_token', $data['data']);
    }

    public function testRefreshReturnsUnauthorizedForInvalidToken(): void
    {
        $controller = $this->makeController();

        $request = new Request(content: json_encode([
            'refresh_token' => bin2hex(random_bytes(32)),
        ]));

        /** @var JsonResponse $response */
        $response = $controller->refresh($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testRefreshReturnsBadRequestWhenTokenMissing(): void
    {
        $controller = $this->makeController();

        $request = new Request(content: json_encode([]));

        /** @var JsonResponse $response */
        $response = $controller->refresh($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }
}
