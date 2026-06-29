<?php

namespace App\Tests\Factory;

use App\Entity\Domain;
use App\Entity\User;
use App\Util\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->email(),
            'username' => self::faker()->unique()->userName(),
            'password' => self::faker()->text(),
            'policy' => PolicyFactory::new(),
            'domain' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $plainPassword = $user->getPassword();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
        });
    }

    public static function class(): string
    {
        return User::class;
    }

    public function user(?Domain $domain = null): self
    {
        return $this->with([
            'domain' => $domain,
            'roles' => '["ROLE_USER"]'
        ])->afterInstantiate(function (User $user): void {
            if ($user->getDomain() === null) {
                $domain = DomainFactory::new()->create(
                    ['domain' => Email::extractDomain($user->getEmail())]
                );
                $user->setDomain($domain);
            }
        });
    }

    public function admin(): self
    {
        return $this->with(['roles' => '["ROLE_ADMIN"]']);
    }

    public function superAdmin(): self
    {
        return $this->with(['roles' => '["ROLE_SUPER_ADMIN"]']);
    }
}
