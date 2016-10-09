<?php
 namespace OC\PlatformBundle\DataFixtures\ORM;

 use Doctrine\Common\DataFixtures\FixtureInterface;
 use Doctrine\Common\Persistence\ObjectManager;
 use OC\UserBundle\Entity\User;

 class LoadUser implements FixtureInterface
 {
     // Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
     public function load(ObjectManager $manager)
     {
         // Liste des noms de catégorie à ajouter
         $listNames = array(
             'Alexandre',
             'Marine',
             'Anna'
         );

         foreach($listNames as $name)
         {
             // On crée la catégorie
             $user = new User;
             $user->setUsername($name);
             $user->setPassword($name);

             $user->setSalt('');
             $user->setRoles(array('ROLE_USER'));

             // On la persiste
             $manager->persist($user);
         }

         // On déclenche l'enregistrement de toutes les catégories
         $manager->flush();
     }
 }