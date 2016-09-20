<?php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\AdvertSkill;
use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OC\PlatformBundle\Repository\AdvertRepository;



class AdvertController extends Controller
{

    public function indexAction($page)
    {
        // On ne sait pas combien de pages il y a
        // Mais on sait qu'une page doit être supérieure ou égale à 1
        if ($page < 1) {
            // On déclenche une exception NotFoundHttpException, cela va afficher
            // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
            throw new NotFoundHttpException('Page "' . $page . '" inexistante.');
        }

        // Ici, on récupérera la liste des annonces, puis on la passera au template

        //Notre liste d'annonce en dur
        $listAdverts = array(
            array(
                'title' => 'Recherche développeur Symfony',
                'id' => 1,
                'author' => 'Alexandre',
                'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon.',
                'date' => new \Datetime()),
            array(
                'title' => 'Mission de webmaster',
                'id' => 2,
                'author' => 'Hugo',
                'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet.',
                'date' => new \Datetime()),
            array(
                'title' => 'Offre de stage webdesigner',
                'id' => 3,
                'author' => 'Mathieu',
                'content' => 'Nous proposons un poste pour webdesigner.',
                'date' => new \Datetime())
        );

        // Mais pour l'instant, on ne fait qu'appeler le template
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array('listAdverts' => $listAdverts));
    }


    public function viewAction($id, Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        // On récupère le repository
        //$repository = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert');

        // On récupère l'entité correspondante à l'id $id
        //$advert = $repository->find($id);
        $advert = $em->find('OCPlatformBundle:Advert', $id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id n'existe pas, d'où ce if:
        if(null == $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On récupère la liste des candidatures de cette annonce
        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(array('advert' => $advert));

        // On récupère maintenant la liste des AdvertSkill
        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert));

        // Le render ne change pas, on passait avant un tableau, maintenant un objet
        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert'=>$advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills
        ));
    }


    public function addAction(Request $request)
    {
        // Création de l'entité
        $advert1 = new Advert();
        $advert1->setTitle('Informaticien');
        $advert1->setAuthor('Jean');
        $advert1->setEmail('poisson.caro@gmail.com');
        $advert1->setContent("Recherche expert en Systèmes d'information pour projet");

        // On peut ne pas définir ni la date ni la publication,
        // car ces attributs sont définis automatiquement dans le constructeur

        // Création de l'entité Image
        $image = new Image();
        $image->setUrl('http://sdz-upload.s3.amazonaws.com/prod/upload/job-de-reve.jpg');
        $image->setAlt('Super job Nantes');

        // On lie l'image à l'annonce
        $advert1->setImage($image);

        // Création d'une première candidature
        $application1 = new Application();
        $application1->setAuthor('Caroline');
        $application1->setContent("J'ai toutes les qualités requises");

        $application2 = new Application();
        $application2->setAuthor('Amandine');
        $application2->setContent('Je suis très motivée');

        // On lie les candidatures à l'annonce
        $application1->setAdvert($advert1);
        $application2->setAdvert($advert1);

        // On récupère l'EntityManager
        $em = $this->getDoctrine()->getManager();

        // On récupère toutes les compétences possibles
        $listsSkills = $em->getRepository('OCPlatformBundle:Skill')->findAll();

        // Pour chaque compétence
        foreach ($listsSkills as $skill)
        {
            //On crée une nouvelle "relation entre 1 annonce et 1 compétence"
            $advertSkill = new AdvertSkill();

            // On la lie à l'annonce, qui est ici toujours la même
            $advertSkill->setAdvert($advert1);
            // On la lie à la compétence, qui change ici dans la boucle foreach
            $advertSkill->setSkill($skill);

            //Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
            $advertSkill->setLevel('Expert');

            // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
            $em->persist($advertSkill);

        }


        // Etape 1 : On "persiste" l'entité
        $em->persist($advert1);

        // Etape 1 bis : si on n'avait pas défini le cascade={"persist"},
        // on devrait persister à la main l'entité $image
        // $em->persist($image);

        // Etape 1 ter : pour cette relation pas de cascade lorsqu'on persiste Advert, car la relation
        // est définie dans l'entité Application et non Advert. On doit donc tout persister à la main ici.

        $em->persist($application1);
        $em->persist($application2);

        // Etape 2 : On "flush" tout ce qui a été persisté avant
        $em->flush();


        // La gestion d'un formulaire est particulière, mais l'idée est la suivante :

        //Si la requête est en POST, c'est que le visiteur a soumis le formulaire
        if ($request->isMethod('POST')) {
            // Ici, on s'occupera de la création et de la gestion du formulaire
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            //Puis on redirige vers la page de visualisation de cette annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert1->getId()));
        }
        return $this->render('OCPlatformBundle:Advert:add.html.twig');
        //$antispam = $this->container->get('oc_platform.antispam');
        //$text = '...';
        //if($antispam->isSpam($text))
        //{
        //    throw new \Exception('Votre message a été détecté comme spam!');
        //}
    }

    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if(null === $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // La méthode findAll retourne toutes les catégories de la base de données
        //$listCategories = $em->getRepository('OCPlatformBundle:Category')->findAll();

        // On boucle sur les catégories pour les lier à l'annonce
        //foreach($listCategories as $category)
        //{
         //   $advert->addCategory($category);
        //}
        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // Etape 2 : On déclenche l'enregistrement
        $em->flush();

        // Ici, on récupérera l'annonce correspondante à $id

        // Même mécanisme que pour l'ajout
       // if($request->isMethod('POST'))
       // {
       //     $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

        //    return $this->redirectToRoute('oc_platform_view', array('id' => 5));
        //}
        //return $this->render('OCPlatformBundle:Advert:edit.html.twig');

        //$advert = array(
          //'title' => 'Recherche développeur Symfony',
            //'id' => 1,
            //'author' => 'Alexandre',
            //'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon.',
            //'date' => new \Datetime()
        //);

        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array('advert' => $advert));
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        // Ici, on récupérera l'annonce correspondant à $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On boucle sur les catégories de l'annonce pour les supprimer
        foreach ($advert->getCategories() as $category)
        {
            $advert->removeCategory($category);
        }

        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // On déclenche la modification
        $em->flush();

        // Ici, on gérera la suppression de l'annonce en question

        return $this->render('OCPlatformBundle:Advert:delete.html.twig');
    }

    public function menuAction($limit)
    {
        // On fixe en dur une liste ici, bien entendu par la suite
        // on la récupérera depuis la BDD !
        $listAdverts = array(
            array('id' => 2, 'title' => 'Recherche développeur Symfony'),
            array('id' => 5, 'title' => 'Mission de webmaster'),
            array('id' => 9, 'title' => 'Offre de stage webdesigner'),
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
            //Tout l'intérêt est ici : le contrôleur passe
            //les variables nécessaires au template !
            'listAdverts' => $listAdverts
        ));
    }

    public function contactAction(Request $request)
    {
        $request->getSession()->getFlashBag()->add('notice','la page de contact n\'est pas disponible. Merci de revenir plus tard. ');

        return $this->redirectToRoute('oc_platform_home');
    }

    public function editImageAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if(null == $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On modifie l'URL de l'image par exemple

        if($advert->getImage() === null)
        {
            $image = new Image;
            $image->setUrl('http://www.w3schools.com/css/trolltunga.jpg');
            $advert->setImage($image);

        }

        $advert->getImage()->setUrl('http://www.w3schools.com/css/trolltunga.jpg');
        $advert->getImage()->setAlt('Good Feeling Work');


        // On n'a pas besoin de persister l'annonce de l'image.
        // Rappelez-vous, ces entités sont automatiquement persistées car
        // on les a récupérées depuis Doctrine lui-même

        // On déclenche la modification
        $em->flush();

        return $this->redirectToRoute('oc_platform_view', array('id' => $id));

    }

    public function testAction($id)
    {
        //$listApplication = $this
         //   ->getDoctrine()
           // ->getManager()
            //->getRepository('OCPlatformBundle:Application')
            //->getApplicationsWithAdvert(2)
    //;

      //  var_dump($listApplication);
        //return $this->render(
          //  'OCPlatformBundle:Advert:test.html.twig',
            //array('listApplication'=>$listApplication
            //));
        //$em = $this->getDoctrine()->getManager();
        //$advert  = $em->getRepository('OCPlatformBundle:Advert')->find($id);
        //$advert->setContent('Cherche développeur Symfony disponible dès maintenant.');
        //$em->flush();

        //return $this->redirectToRoute('oc_platform_view', array('id' =>$id));

        //$em = $this->getDoctrine()->getManager();
        //$advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        //if($advert == null)
        //{
        //    throw new NotFoundHttpException('L\'annonce d\'id n°'.$id.' n\'existe pas.');
        //}

        //return $this->render('OCPlatformBundle:Advert:test.html.twig', array('advert'=>$advert));

        $advert = new Advert();
        $advert->setTitle("Recherche développeur !");
        $advert->setContent("Cherchons un développeur Symfony pour début novembre");
        $advert->setAuthor("Benjamin");
        $advert->setEmail("bj@gmail.com");

        $em = $this->getDoctrine()->getManager();
        $em->persist($advert);
        $em->flush(); // C'est à ce moment qu'est généré le slug

        return new Response('Slug généré : '.$advert->getSlug());
        // Affiche "Slug généré : recherche-développeur"
    }

    public function addApplicationAction($id)
    {
        $application1 = new Application;
        $application1->setAuthor('Auriane');
        $application1->setContent('Je suis la femme de la situation');

        $em = $this->getDoctrine()->getManager();
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);
        $advert->addApplication($application1);

        $em->persist($application1);
        $em->flush();

        return $this->redirectToRoute('oc_platform_test', array('id' => $id));
    }

    public function removeApplicationAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        $applications = $em->getRepository('OCPlatformBundle:Application')->findApplicationsByAuthor('Auriane');

        var_dump($applications);

        foreach($applications as $application)
        {
            $advert->removeApplication($application);
        }

        return $this->redirectToRoute('oc_platform_test', array('id'=>$id));
    }
}