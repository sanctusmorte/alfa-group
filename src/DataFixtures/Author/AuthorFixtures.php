<?php

namespace App\DataFixtures\Author;

use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AuthorFixtures extends Fixture
{
    /**
     * @var array
     */
    private $authors;

    /**
     * AuthorFixtures constructor.
     * @param AuthorFixturesData $authorFixturesData
     */
    public function __construct(AuthorFixturesData $authorFixturesData)
    {
        $this->authors = $authorFixturesData->getAuthors();
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->authors as $author) {
            $newAuthor = new Author();
            $newAuthor->setName($author['name']);
            $newAuthor->setSurname($author['surname']);
            $newAuthor->setPatronymic($author['patronymic']);
            $manager->persist($newAuthor);
        }

        $manager->flush();
    }
}
