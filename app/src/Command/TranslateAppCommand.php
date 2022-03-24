<?php

namespace App\Command;

use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslateAppCommand extends Command {

    protected static $defaultName = 'agentj:translate-app';
    private $client;
    private $source;
    private $target;

    public function __construct(HttpClientInterface $client) {

        
        $this->client = $client;   
        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('Translate app');
        $this->addArgument('source', InputArgument::REQUIRED, 'Source language')
            ->addArgument('target', InputArgument::REQUIRED, 'Target langauge');        
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
//        $translator = $this->getContainer()->get('translator');

        $io = new SymfonyStyle($input, $output);

        $this->source = $input->getArgument('source');
        $this->target = $input->getArgument('target');
        
        $value = Yaml::parseFile('translations/messages.' . $this->source . '.yml');
        $this->parse($value);
        $translatedDoc = $this->parse($value);
        file_put_contents('translations/messages.' . $this->target. '.yml', Yaml::dump($translatedDoc, 4));
        
        return Command::SUCCESS;
    }

    function parse($doc) {
        foreach ($doc as $key => $val) {
            if (is_array($val)) {
                $doc[$key] = $this->parse($val);
            } else {
                $newVal = $this->translate($val);
                $doc[$key] = $newVal;
            }
        }
        return $doc;
    }

    function translate($str) {
        $response = $this->client->request(
                'POST',
                'http://172.30.101.45:5000/translate',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'q' => $str, 
                        'source' => $this->source, 
                        'target' => $this->target,
                        'format' => 'html'
                        ]),
                ]
        );

        if ($response->getStatusCode() === 200){
            $content = json_decode($response->getContent());
            $return = ctype_upper($str[0]) ? ucfirst($content->translatedText) : $content->translatedText;                        
            return $return;
        }
        else{
            echo $response->getStatusCode() . "\n";
        }
        return $str;
    }

}
