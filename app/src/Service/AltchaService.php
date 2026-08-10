<?php

namespace App\Service;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\SolveChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AltchaService
{
    private Altcha $altcha;
    private Pbkdf2 $pbkdf2;

    public function __construct(
        #[Autowire(env: 'ALTCHA_KEY_SECRET')]
        private string $keySecret,
    ) {
        $this->altcha = new Altcha($this->keySecret ?: null);
        $this->pbkdf2 = new Pbkdf2();
    }

    public function buildChallenge(int $cost = 5000): Challenge
    {
        $challengeOptions = new CreateChallengeOptions(
            algorithm: $this->pbkdf2,
            cost: $cost,
            counter: random_int(5000, 10000),
            expiresAt: (new DateTimeImmutable())->modify('+10 minutes'),
        );

        return $this->altcha->createChallenge($challengeOptions);
    }

    public function verifySolution(string $field): bool
    {
        [
            $challengeParameters,
            $challengeSignature,
            $solutionCounter,
            $solutionDerivedKey,
        ] = $this->parseAltchaField($field);

        $challengeParameters = ChallengeParameters::fromArray($challengeParameters);

        $challenge = new Challenge(
            $challengeParameters,
            $challengeSignature,
        );
        $solution = new Solution(
            counter: $solutionCounter,
            derivedKey: $solutionDerivedKey,
        );

        $payload = new Payload($challenge, $solution);

        $solutionOptions = new VerifySolutionOptions(
            payload: $payload,
            algorithm: $this->pbkdf2,
        );

        return $this->altcha->verifySolution($solutionOptions)->verified;
    }

    /**
     * @return array{mixed[], string, int, string}
     */
    private function parseAltchaField(string $field): array
    {
        $decoded = base64_decode($field, strict: true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64 altcha value.');
        }

        $payload = json_decode($decoded, associative: true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Invalid JSON in altcha value.');
        }

        $challenge = $payload['challenge'] ?? [];
        $solution = $payload['solution'] ?? [];

        if (!is_array($challenge) || !is_array($solution)) {
            throw new \RuntimeException('Invalid JSON value (challenge or solution) in altcha value.');
        }

        $challengeParameters = $challenge['parameters'] ?? [];
        if (!is_array($challengeParameters)) {
            $challengeParameters = [];
        }

        $challengeSignature = $challenge['signature'] ?? '';
        if (!is_string($challengeSignature)) {
            $challengeSignature = '';
        }

        $solutionCounter = $solution['counter'] ?? 0;
        $solutionCounter = is_numeric($solutionCounter) ? (int)$solutionCounter : 0;

        $solutionDerivedKey = $solution['derivedKey'] ?? '';
        $solutionDerivedKey = is_string($solutionDerivedKey) ? $solutionDerivedKey : '';

        return [$challengeParameters, $challengeSignature, $solutionCounter, $solutionDerivedKey];
    }
}
