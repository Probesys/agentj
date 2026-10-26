<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026094041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update default report mail to use new categories variables.';
    }

    public function up(Schema $schema): void
    {
        // phpcs:disable
        $this->addSql(<<<SQL
            UPDATE `settings`
            SET value = '<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n<tbody><tr style="display:none !important;font-size:1px;"><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td align="center" valign="top"><table class="table layout-table templateContainer" style="background-color:#fff;max-width:590px!important;width:590px;" role="presentation" cellpadding="7" cellspacing="0" width="100%"><tbody><tr><td align="center" valign="top"><table class="table layout-table rnb-col-1" role="presentation" align="top" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td align="center"><a href="https://www.agentj.io" title="Solution antispam AgentJ"><img class="image_resized" style="width:300px;" src="https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png" alt="Solution antispam AgentJ" title="Solution antispam AgentJ"></a></td></tr><tr><td style="color:#3c4858;font-size:14px;"><h3 style="color:#e8576b;font-size:18px;margin-bottom:0px;text-align:center;text-transform:uppercase;"><strong>RAPPORT AGENTJ</strong></h3></td></tr><tr><td style="font-size:1px;line-height:0px;mso-hide:all;" height="20">&nbsp;</td></tr><tr><td style="color:#3c4858;font-size:14px;"><p>Bonjour <strong>[USERNAME]</strong>,</p><p>Ci dessous la liste des mails non traités, en attente sur AgentJ :</p><p><strong>Messages standards :</strong></p><p>[LIST_MESSAGES_STANDARD]</p><p><strong>Messages de listes de diffusion :</strong></p><p>[LIST_MESSAGES_LIST]</p><p><strong>Messages détectés comme spam :</strong></p><p>[LIST_MESSAGES_SPAM]</p></td></tr><tr><td style="color:#3c4858;font-family:Arial,Helvetica,sans-serif, sans-serif;font-size:14px;">Merci et bonne journée.</td></tr></tbody></table></td></tr></tbody></table></td><td>&nbsp;</td></tr></tbody></table>'
            WHERE context = 'default_domain_messages'
            AND name = 'mail_content_report'
        SQL);
        // phpcs:enable
    }

    public function down(Schema $schema): void
    {
        // phpcs:disable
        $this->addSql(<<<SQL
            UPDATE `settings`
            SET value = '<link href=\"https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,400;0,500;1,800&amp;display=swap\" rel=\"stylesheet\" /><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"x-apple-disable-message-reformatting\" /><meta name=\"apple-mobile-web-app-capable\" content=\"yes\" /><meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" /><meta name=\"format-detection\" content=\"telephone=no\" />\n<title></title>\n<table align=\"center\" bgcolor=\"#f9fafc\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"main-template\" style=\"background-color: #E5E5E5;;\" width=\"100%\">\n	<tbody>\n		<tr style=\"display:none !important; font-size:1px;\">\n			<td>&nbsp;</td>\n			<td>&nbsp;</td>\n		</tr>\n		<tr>\n			<td align=\"center\" valign=\"top\">\n			<table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" class=\"templateContainer\" style=\"background-color:#fff;max-width:590px!important; width: 590px;\" width=\"100%\">\n				<tbody>\n					<tr>\n						<td align=\"center\" valign=\"top\">\n						<table align=\"top\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"rnb-col-1\" width=\"100%\">\n							<tbody>\n								<tr>\n									<td align=\"center\"><a href=\"https://www.agentj.io\" title=\"Solution antispam AgentJ\"><img alt=\"Solution antispam AgentJ\" src=\"https://agentj.io/sites/agentj.io/themes/agentj/logo-agentj.png\" style=\"width:300px\" title=\"Solution antispam AgentJ\" /> </a></td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; color:#3c4858;\">\n									<h3 style=\"font-weight: bold; font-size: 18px; color: #e8576b; text-align: center; text-transform: uppercase;margin-bottom: 0px;\">RAPPORT AGENTJ</h3>\n									</td>\n								</tr>\n								<tr>\n									<td height=\"20\" style=\"font-size:1px; line-height:0px; mso-hide: all;\">&nbsp;</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px;  color:#3c4858;\">\n									<p>Bonjour <strong>[USERNAME]</strong>,</p>\n\n									<p>Ci dessous la liste des mails non trait&eacute;s, en attente sur AgentJ</p>\n\n									<p>[LIST_MAIL_MSGS]</p>\n									</td>\n								</tr>\n								<tr>\n									<td style=\"font-size:14px; font-family:Arial,Helvetica,sans-serif, sans-serif; color:#3c4858;\">&nbsp;\n									<p>Merci et bonne journ&eacute;e.</p>\n									</td>\n								</tr>\n							</tbody>\n						</table>\n						</td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n		</tr>\n	</tbody>\n</table>\n'
            WHERE context = 'default_domain_messages'
            AND name = 'mail_content_report'
        SQL);
        // phpcs:enable
    }
}
