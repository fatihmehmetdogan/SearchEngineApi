<?php

namespace App\DataFixtures;

use App\Service\Provider\ProviderManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private ProviderManager $providerManager // ProviderManager'ı inject ediliyor
    ) {}

    public function load(ObjectManager $manager)
    {
        // ProviderManager aracılığıyla tüm dokümanları çek ve işle
        $documentsToPersist = $this->providerManager->fetchAndProcessAllContent();

        foreach ($documentsToPersist as $document) {
            $manager->persist($document);
        }

        $manager->flush();
    }
}