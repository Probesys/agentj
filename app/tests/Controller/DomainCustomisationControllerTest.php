<?php

namespace App\Tests\Controller;

use App\Service\FileUploader;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DomainCustomisationControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const LOGO_FIXTURE = __DIR__ . '/../../public/img/logo-512.png';

    private KernelBrowser $client;

    private Filesystem $filesystem;

    /**
     * The upload directory of the test environment, emptied after each test.
     */
    private string $uploadDirectory;

    /**
     * A temporary directory for the files to upload.
     */
    private string $filesDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->loginUser(UserFactory::new()->superAdmin()->create());

        $this->filesystem = new Filesystem();
        $this->uploadDirectory = static::getContainer()->getParameter('app.upload_directory');
        $this->filesystem->mkdir($this->uploadDirectory);
        $this->filesDirectory = $this->filesystem->tempnam(sys_get_temp_dir(), 'logos');
        $this->filesystem->remove($this->filesDirectory);
        $this->filesystem->mkdir($this->filesDirectory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove([$this->uploadDirectory, $this->filesDirectory]);
        parent::tearDown();
    }

    public function testAnHtmlFileIsRefusedAsLogo(): void
    {
        $domain = DomainFactory::createOne(['logo' => 'logo-actuel.png']);
        $htmlFilePath = $this->filesDirectory . '/logo.png';
        file_put_contents($htmlFilePath, '<html><body><script>alert(document.cookie)</script></body></html>');

        $this->submitLogo($domain->getId(), $htmlFilePath);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('logo-actuel.png', DomainFactory::find($domain->getId())->getLogo());
    }

    public function testATooBigLogoIsRefused(): void
    {
        $domain = DomainFactory::createOne(['logo' => 'logo-actuel.png']);
        // A valid PNG image, made bigger than 1 MB.
        $bigLogoFilePath = $this->filesDirectory . '/logo.png';
        copy(self::LOGO_FIXTURE, $bigLogoFilePath);
        file_put_contents($bigLogoFilePath, str_repeat("\0", 2 * 1024 * 1024), FILE_APPEND);

        $this->submitLogo($domain->getId(), $bigLogoFilePath);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('logo-actuel.png', DomainFactory::find($domain->getId())->getLogo());
    }

    public function testASvgLogoIsAccepted(): void
    {
        $domain = DomainFactory::createOne(['logo' => null]);
        $svgFilePath = $this->filesDirectory . '/logo.svg';
        file_put_contents(
            $svgFilePath,
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>',
        );

        $this->submitLogo($domain->getId(), $svgFilePath);

        self::assertResponseRedirects();
        $logo = (string) DomainFactory::find($domain->getId())->getLogo();
        self::assertMatchesRegularExpression('/^logo-[0-9a-f]{16}\.svg$/', $logo);
        self::assertFileExists($this->uploadDirectory . $logo);
    }

    public function testTheLogoGetsAUniqueName(): void
    {
        $domain1 = DomainFactory::createOne(['logo' => null]);
        $domain2 = DomainFactory::createOne(['logo' => null]);

        $this->uploadLogo($domain1->getId());
        $this->uploadLogo($domain2->getId());

        $logo1 = (string) DomainFactory::find($domain1->getId())->getLogo();
        $logo2 = (string) DomainFactory::find($domain2->getId())->getLogo();
        self::assertMatchesRegularExpression('/^logo-[0-9a-f]{16}\.png$/', $logo1);
        self::assertMatchesRegularExpression('/^logo-[0-9a-f]{16}\.png$/', $logo2);
        self::assertNotSame($logo1, $logo2);
        self::assertFileExists($this->uploadDirectory . $logo1);
        self::assertFileExists($this->uploadDirectory . $logo2);
    }

    public function testThePreviousLogoIsRemoved(): void
    {
        copy(self::LOGO_FIXTURE, $this->uploadDirectory . 'previous-logo.png');
        $domain = DomainFactory::createOne(['logo' => 'previous-logo.png']);

        $this->uploadLogo($domain->getId());

        $logo = (string) DomainFactory::find($domain->getId())->getLogo();
        self::assertNotSame('previous-logo.png', $logo);
        self::assertFileDoesNotExist($this->uploadDirectory . 'previous-logo.png');
        self::assertFileExists($this->uploadDirectory . $logo);
    }

    public function testAPreviousLogoSharedWithAnotherDomainIsKept(): void
    {
        copy(self::LOGO_FIXTURE, $this->uploadDirectory . 'shared-logo.png');
        $domain = DomainFactory::createOne(['logo' => 'shared-logo.png']);
        $otherDomain = DomainFactory::createOne(['logo' => 'shared-logo.png']);

        $this->uploadLogo($domain->getId());

        $logo = (string) DomainFactory::find($domain->getId())->getLogo();
        self::assertNotSame('shared-logo.png', $logo);
        self::assertSame('shared-logo.png', DomainFactory::find($otherDomain->getId())->getLogo());
        self::assertFileExists($this->uploadDirectory . 'shared-logo.png');
    }

    public function testAnErrorIsDisplayedIfTheLogoCannotBeSaved(): void
    {
        $domain = DomainFactory::createOne(['logo' => 'logo-actuel.png']);
        // Simulate a failure when moving the file (e.g. permissions, full disk).
        $fileUploader = $this->createMock(FileUploader::class);
        $fileUploader->method('upload')->willReturn('');
        // Keep the mock for the next requests.
        $this->client->disableReboot();
        static::getContainer()->set(FileUploader::class, $fileUploader);
        $logoFilePath = $this->filesDirectory . '/logo.png';
        copy(self::LOGO_FIXTURE, $logoFilePath);

        $this->submitLogo($domain->getId(), $logoFilePath);

        self::assertResponseStatusCodeSame(422);
        $translator = static::getContainer()->get(TranslatorInterface::class);
        self::assertSelectorTextContains(
            'form[name="domain_customisation_general"]',
            $translator->trans('Message.Flash.logoUploadFailed', locale: 'en'),
        );
        self::assertSame('logo-actuel.png', DomainFactory::find($domain->getId())->getLogo());
    }

    private function uploadLogo(?int $domainId): void
    {
        // The original name of the uploaded file is the name of this copy.
        $logoFilePath = $this->filesDirectory . '/logo.png';
        copy(self::LOGO_FIXTURE, $logoFilePath);

        $this->submitLogo($domainId, $logoFilePath);

        self::assertResponseRedirects();
    }

    private function submitLogo(?int $domainId, string $filePath): void
    {
        $crawler = $this->client->request('GET', '/domain/' . $domainId . '/customisation');
        $form = $crawler->filter('form[name="domain_customisation_general"]')->form();
        $field = $form['domain_customisation_general[logoFile]'];
        self::assertInstanceOf(FileFormField::class, $field);
        $field->upload($filePath);
        $this->client->submit($form);
    }
}
