<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DomainCustomisationControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->loginUser(UserFactory::new()->superAdmin()->create());
    }

    public function testAnHtmlFileIsRefusedAsLogo(): void
    {
        $domain = DomainFactory::createOne(['logo' => 'logo-actuel.png']);

        $htmlFilePath = (string) tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($htmlFilePath, '<html><body><script>alert(document.cookie)</script></body></html>');

        $crawler = $this->client->request('GET', '/domain/' . $domain->getId() . '/customisation');
        $form = $crawler->filter('form[name="domain_customisation_general"]')->form();
        $field = $form['domain_customisation_general[logoFile]'];
        self::assertInstanceOf(FileFormField::class, $field);
        $field->upload($htmlFilePath);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('logo-actuel.png', DomainFactory::find($domain->getId())->getLogo());
        unlink($htmlFilePath);
    }
}
