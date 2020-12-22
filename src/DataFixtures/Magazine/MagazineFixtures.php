<?php

namespace App\DataFixtures\Magazine;

use App\DataFixtures\Author\AuthorFixtures;
use App\Entity\Magazine;
use App\Repository\AuthorRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MagazineFixtures extends Fixture implements DependentFixtureInterface
{

    /**
     * @var array
     */
    private $magazines;

    /**
     * @var AuthorRepository
     */
    private $authorRepository;

    /**
     * MagazineFixtures constructor.
     * @param \App\DataFixtures\Magazine\MagazineFixturesData $magazineFixturesData
     * @param AuthorRepository $authorRepository
     */
    public function __construct(MagazineFixturesData $magazineFixturesData,
                                AuthorRepository $authorRepository)
    {
        $this->magazines = $magazineFixturesData->getMagazinesForFixtures();
        $this->authorRepository = $authorRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->magazines as $magazine) {
            $newMagazine = new Magazine();
            $newMagazine->setName($magazine['name']);
            $newMagazine->setDescription($magazine['description']);
            $newMagazine->setImageUrl('');
            $newMagazine = $this->setAuthors($newMagazine, $magazine);
            $manager->persist($newMagazine);
        }
        $manager->flush();
    }

    /**
     * @param Magazine $newMagazine
     * @param array $magazine
     * @return Magazine
     */
    private function setAuthors(Magazine $newMagazine, array $magazine): Magazine
    {
        foreach ($magazine['authorsIds'] as $id) {
            if (is_int($id)) {
                $existAuthor = $this->authorRepository->find($id);
                if ($existAuthor !== null) {
                    $newMagazine->addAuthor($existAuthor);
                }
            }
        }
        return $newMagazine;
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            AuthorFixtures::class,
        ];
    }
}
