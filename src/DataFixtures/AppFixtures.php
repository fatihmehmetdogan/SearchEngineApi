<?php

namespace App\DataFixtures;

use App\Entity\Document;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 20; $i++) {
            $document = new Document();
            $document->setTitle("Test Document $i");
            $document->setContent("This is test content for document $i");
            $document->setType($i % 2 === 0 ? 'video' : 'text');
            $document->setUrl("https://example.com/content/$i");
            
            // Set metrics based on content type
            if ($document->getType() === 'video') {
                $document->setViews(rand(1000, 10000));
                $document->setLikes(rand(100, 1000));
            } else {
                $document->setViews(rand(100, 1000));
                $document->setLikes(rand(10, 100));
            }
            
            // Set category and tags
            $document->setCategory($i % 3 === 0 ? 'Technology' : ($i % 3 === 1 ? 'Science' : 'History'));
            $document->setTags(['tag' . ($i % 5), 'tag' . ($i % 3)]);
            
            $manager->persist($document);
        }

        $manager->flush();
    }
}