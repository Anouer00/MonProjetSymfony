<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // création de l'administrateur
        $admin = new User();
        $admin->setEmail('admin@api.com')
              ->setRoles(['ROLE_ADMIN'])
              ->setIsVerified(true)
              ->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        // création de l'utilisateur standard
        $user = new User();
        $user->setEmail('user@api.com')
              ->setRoles(['ROLE_USER'])
              ->setIsVerified(true)
              ->setPassword($this->hasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // génération de 50 livres
        for ($i = 0; $i < 50; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3))
                 ->setAuthor($faker->name())
                 ->setIsbn($faker->isbn13())
                 ->setPublishedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 years', 'now')))
                 ->setDescription($faker->paragraph());

            $manager->persist($book);
        }

        $manager->flush();
    }
}