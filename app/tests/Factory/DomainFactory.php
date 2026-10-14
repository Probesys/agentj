<?php

namespace App\Tests\Factory;

use App\Entity\Domain;
use App\Entity\SenderRule;
use App\Repository\SenderRuleRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Domain>
 */
final class DomainFactory extends PersistentObjectFactory
{
    public function __construct(private SenderRuleRepository $senderRuleRepository)
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Domain::class;
    }

    public function withDomain(string $domain): self
    {
        return $this->with([
            'domain' => $domain,
            'srvSmtp' => 'smtp.' . $domain,
        ]);
    }

    #[\Override]
    protected function defaults(): array
    {
        $domain = sprintf(
            '%s-%s.%s',
            self::faker()->domainWord(),
            bin2hex(random_bytes(4)),
            self::faker()->tld(),
        );

        return [
            'active' => true,
            'domain' => $domain,
            'srvSmtp' => 'smtp.' . $domain,
            'smtpPort' => 25,
            'domainKeys' => DomainKeyFactory::new(),
            'policy' => PolicyFactory::new(),
            'logo' => self::faker()->imageUrl(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (Domain $domain): void {
                $domain->setCalculatedTransport();
            })
            ->afterPersist(function (Domain $domain) {
                $user = UserFactory::new()->create([
                    'email' => '@' . $domain->getDomain(),
                    'domain' => $domain,
                    'priority' => 2,
                ]);

                $rootMailAddr = RuleAddressFactory::findOrCreate(['email' => '@.']);

                $senderRule = new SenderRule($user, $rootMailAddr);
                $senderRule->setWbRule('enabled');
                $senderRule->setPriority(SenderRule::PRIORITY_DOMAIN);

                $this->senderRuleRepository->save($senderRule);
            })
        ;
    }

    /**
     * @param 'simple'|'full' $reportType
     */
    public function withReport(string $reportType): self
    {
        // phpcs:disable Generic.Files.LineLength
        if ($reportType === 'simple') {
            $messageAlert = '<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n<tbody><tr style="display:none !important;font-size:1px;"><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td align="center" valign="top"><table class="table layout-table templateContainer" style="background-color:#fff;max-width:590px!important;width:590px;" role="presentation" cellpadding="7" cellspacing="0" width="100%"><tbody><tr><td align="center" valign="top"><table class="table layout-table rnb-col-1" role="presentation" align="top" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td align="center"><a href="https://www.agentj.io" title="Solution antispam AgentJ"><img class="image_resized" style="width:300px;" src="https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png" alt="Solution antispam AgentJ" title="Solution antispam AgentJ"></a></td></tr><tr><td style="color:#3c4858;font-size:14px;"><h3 style="color:#e8576b;font-size:18px;margin-bottom:0px;text-align:center;text-transform:uppercase;"><strong>RAPPORT AGENTJ</strong></h3></td></tr><tr><td style="font-size:1px;line-height:0px;mso-hide:all;" height="20">&nbsp;</td></tr><tr><td style="color:#3c4858;font-size: 14px;"><p>Bonjour <strong>[USERNAME]</strong>,</p><p>Ci dessous la liste des mails non traités, en attente sur AgentJ :</p>[LIST_MAIL_MSGS][IF_MESSAGES_STANDARD]<p><strong>Messages standards :</strong></p>[LIST_MESSAGES_STANDARD][ENDIF_MESSAGES_STANDARD][IF_MESSAGES_LIST]<p><strong>Messages de listes de diffusion :</strong></p>[LIST_MESSAGES_LIST][ENDIF_MESSAGES_LIST][IF_MESSAGES_SPAM]<p><strong>Messages détectés comme spam :</strong></p>[LIST_MESSAGES_SPAM][ENDIF_MESSAGES_SPAM]</td></tr><tr><td style="color:#3c4858;font-family:Arial,Helvetica,sans-serif, sans-serif;font-size:14px;">Merci et bonne journée.</td></tr></tbody></table></td></tr></tbody></table></td><td>&nbsp;</td></tr></tbody></table>';
        } else {
            $messageAlert = '<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n<tbody><tr style="display:none !important;font-size:1px;"><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td align="center" valign="top"><table class="table layout-table templateContainer" style="background-color:#fff;max-width:590px!important;width:590px;" role="presentation" cellpadding="7" cellspacing="0" width="100%"><tbody><tr><td align="center" valign="top"><table class="table layout-table rnb-col-1" role="presentation" align="top" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td align="center"><a href="https://www.agentj.io" title="Solution antispam AgentJ"><img class="image_resized" style="width:300px;" src="https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png" alt="Solution antispam AgentJ" title="Solution antispam AgentJ"></a></td></tr><tr><td style="color:#3c4858;font-size:14px;"><h3 style="color:#e8576b;font-size:18px;margin-bottom:0px;text-align:center;text-transform:uppercase;"><strong>RAPPORT AGENTJ</strong></h3></td></tr><tr><td style="font-size:1px;line-height:0px;mso-hide:all;" height="20">&nbsp;</td></tr><tr><td style="color:#3c4858;font-size: 14px;"><p>Bonjour <strong>[USERNAME]</strong>,</p><p>Ci dessous la liste des mails non traités, en attente sur AgentJ :</p>[IF_MESSAGES_STANDARD]<p><strong>Messages standards :</strong></p>[LIST_MESSAGES_STANDARD][ENDIF_MESSAGES_STANDARD][IF_MESSAGES_LIST]<p><strong>Messages de listes de diffusion :</strong></p>[LIST_MESSAGES_LIST][ENDIF_MESSAGES_LIST][IF_MESSAGES_SPAM]<p><strong>Messages détectés comme spam :</strong></p>[LIST_MESSAGES_SPAM][ENDIF_MESSAGES_SPAM]</td></tr>[FOREACH_MESSAGE_LIST]</p><p>[MESSAGE_SENDER]</p><p>[MESSAGE_SUBJECT]</p><p><a href="[URL_MESSAGE_AUTHORIZE_SENDER]">[URL_MESSAGE_AUTHORIZE_SENDER]</a></p><p><a href="[URL_MESSAGE_RESTORE]">[URL_MESSAGE_RESTORE]</a></p><p><a href="[URL_MESSAGE_BAN]">[URL_MESSAGE_BAN]</a></p><p><a href="[URL_MESSAGE_UNSUBSCRIBE]">[URL_MESSAGE_UNSUBSCRIBE]</a></p><p>[ENDFOREACH_MESSAGE_LIST]</p><p><a href="[URL_MSGS]">[URL_MSGS]</a></p><tr><td style="color:#3c4858;font-family:Arial,Helvetica,sans-serif, sans-serif;font-size:14px;">Merci et bonne journée.</td></tr></tbody></table></td></tr></tbody></table></td><td>&nbsp;</td></tr></tbody></table>';
        }
        // phpcs:enable Generic.Files.LineLength

        return $this->with([
            'messageAlert' => $messageAlert,
        ]);
    }
}
