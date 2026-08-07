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
            'originalUser' => null,
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

    public function alias(User $user): self
    {
        return $this->with([
            'originalUser' => $user,
        ]);
    }

    public function user(?Domain $domain = null): self
    {
        return $this->with(function () use ($domain) {
            $email = $domain
                ? self::faker()->userName() . '@' . $domain->getDomain()
                : self::faker()->unique()->email();

            return [
                'email' => $email,
                'domain' => $domain,
                'roles' => '["ROLE_USER"]',
            ];
        })->afterInstantiate(function (User $user) use ($domain): void {
            if ($user->getDomain() === null) {
                $domainName = Email::extractDomain($user->getEmail());

                $domain = DomainFactory::new()
                    ->withDomain($domainName)
                    ->create();

                $user->setDomain($domain);
            }
        });
    }

    /**
     * @param array<Domain>|null $domains
     */
    public function admin(?array $domains = []): self
    {
        return $this->with(function () {
            return [
                'email' => self::faker()->unique()->email(),
                'roles' => '["ROLE_ADMIN"]',
            ];
        })->afterInstantiate(function (User $user) use ($domains): void {
            foreach ($domains as $domain) {
                $user->addDomain($domain);
            }
        });
    }

    public function superAdmin(): self
    {
        return $this
            ->with([
                'email' => '',
                'roles' => '["ROLE_SUPER_ADMIN"]'
            ]);
    }
}
