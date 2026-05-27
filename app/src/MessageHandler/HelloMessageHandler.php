<?php

namespace App\MessageHandler;

use App\Message\HelloMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsMessageHandler]
class HelloMessageHandler
{
    private Application $application;

    public function __construct(
        KernelInterface $kernel,
    ) {
        $this->application = new Application($kernel);
    }

    public function __invoke(HelloMessage $message)
    {
        $message = $message->getContent() ?: 'World';
        var_dump('Hello, '.$message);
        sleep(5);
    }
}
