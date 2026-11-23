<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ImportControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->loginUser(UserFactory::new()->superAdmin()->create());
        $this->translator = static::getContainer()->get(TranslatorInterface::class);
    }

    public function testUsersAreImportedFromACsvFile(): void
    {
        DomainFactory::createOne(['domain' => 'example.com']);

        // With ";" separators, the file is detected as text/plain.
        $this->importFile('/admin/import/users', 'john@example.com;Doe;John;Group' . "\n");

        self::assertSelectorTextContains(
            '.flash-success',
            str_replace('[NB_USER]', '1', $this->translator->trans('Generics.flash.ImportSuccess', locale: 'en')),
        );
        self::assertSame(1, UserFactory::count(['email' => 'john@example.com']));
    }

    public function testAnHtmlFileSentAsCsvIsRefused(): void
    {
        DomainFactory::createOne(['domain' => 'example.com']);

        $this->importFile('/admin/import/users', '<html><body><p>john@example.com;Doe;John;Group</p></body></html>');

        self::assertSelectorTextContains(
            '.alert-danger',
            $this->translator->trans('Generics.flash.BadCsvFormat', locale: 'en'),
        );
        self::assertSame(0, UserFactory::count(['email' => 'john@example.com']));
    }

    public function testAliasesAreImportedFromACsvFile(): void
    {
        $domain = DomainFactory::createOne(['domain' => 'example.com']);
        $user = UserFactory::createOne(['email' => 'john@example.com', 'domain' => $domain]);

        $this->importFile('/admin/import/users_alias', 'John;Doe;john@example.com;alias@example.com' . "\n");

        self::assertSelectorTextContains(
            '.flash-success',
            str_replace('[NB_USER]', '1', $this->translator->trans('Generics.flash.ImportSuccess', locale: 'en')),
        );
        $alias = UserFactory::find(['email' => 'alias@example.com']);
        self::assertSame($user->getId(), $alias->getOriginalUser()?->getId());
    }

    public function testAnHtmlFileSentAsCsvIsRefusedForAliases(): void
    {
        $domain = DomainFactory::createOne(['domain' => 'example.com']);
        UserFactory::createOne(['email' => 'john@example.com', 'domain' => $domain]);

        $this->importFile(
            '/admin/import/users_alias',
            '<html><body><p>John;Doe;john@example.com;alias@example.com</p></body></html>',
        );

        self::assertSelectorTextContains(
            '.alert-danger',
            $this->translator->trans('Generics.flash.BadCsvFormat', locale: 'en'),
        );
        self::assertSame(0, UserFactory::count(['email' => 'alias@example.com']));
    }

    private function importFile(string $url, string $content): void
    {
        $filePath = (string) tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($filePath, $content);

        // The browser claims that the file is a CSV, whatever its content.
        $file = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        $csrfToken = $crawler->filter('input[name="import[_token]"]')->attr('value');
        $this->client->request(Request::METHOD_POST, $url, [
            'import' => ['_token' => $csrfToken],
        ], [
            'import' => ['attachment' => $file],
        ], [
            // Go back to a page that displays the flash messages.
            'HTTP_REFERER' => 'http://localhost/',
        ]);

        @unlink($filePath);
        self::assertResponseRedirects();
        $this->client->followRedirect();
    }
}
