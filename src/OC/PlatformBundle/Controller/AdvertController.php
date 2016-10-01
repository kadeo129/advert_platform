<?php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Purge\OCPurge;
use OC\PlatformBundle\Entity\AdvertSkill;
use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OC\PlatformBundle\Repository\AdvertRepository;
use OC\PlatformBundle\Form\AdvertType;


class AdvertController extends Controller
{
    // affiche toutes les annonces paginées
    public function indexAction($page)
    {
        // définit le nombre maximal d'annonce par page
        $maxAdverts = $this->getParameter('max_per_page');

        // compte le nombre total d'annonces
        $adverts_count = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('OCPlatformBundle:Advert')
            ->countPublishedTotal()
        ;

        // tableau contenant toutes les données nécessaires à la pagination
        $pagination = array(
            'page' => $page,
            'route' => 'oc_platform_home',
            'pages_count'=>ceil($adverts_count / $maxAdverts),
            'route_params' => array()
        );

        // si la page demandée n'existe pas
        if ($page>$pagination['pages_count'] OR $page < 1)
        {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        //Récupère les annonces par listes de $maxAdverts selon la page indiquée en paramètre
        $listAdverts = $this
                        ->getDoctrine()
                        ->getManager()
                        ->getRepository('OCPlatformBundle:Advert')
                        ->getAdverts($page,$maxAdverts)
        ;

        // pour chaque annonce, récupération des attributs
        foreach($listAdverts as $advert)
        {
            if($advert->getImage()==null)
            {
                $img = new Image();
                $img->setUrl('https://static.pexels.com/photos/63572/pexels-photo-63572.jpeg');
                $img->setAlt('Jolie image pour job fabuleux');

                $em = $this->getDoctrine()->getManager();
                $advert->setImage($img);
                $advert = $em->find('OCPlatformBundle:Advert', $advert->getId());
                $em->flush();
            }
            $advert->getImage();
        }

        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts,
            'pagination' => $pagination
        ));
    }

    // affiche une annonce et ses attributs
    public function viewAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $advert = $em->find('OCPlatformBundle:Advert', $id);

        if(null == $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(array('advert' => $advert))
        ;

        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert));

        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert'=>$advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills
        ));
    }

    // ajoute une annonce, une image, deux candidature, ses catégories ainsi que les compétences requises:: À compléter avec un formulaire
    public function addAction(Request $request)
    {
        $advert = new Advert();

        $form = $this->createForm(AdvertType::class,$advert);
        if($request->isMethod('POST') && $form->handleRequest($request)->isValid())
        {
                $em = $this->getDoctrine()->getManager();
                $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

                return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }
        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    // permet d'éditer une annonce :: À compléter avec un formulaire!
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if(null === $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        $advert->setAuthor('Paule');
        if($advert->getImage() === null)
        {
            $image = new Image;
            $image->setUrl('http://www.w3schools.com/css/trolltunga.jpg');
            $advert->setImage($image);
        }
        $advert->getImage()->setUrl('https://static.pexels.com/photos/63572/pexels-photo-63572.jpeg');
        $advert->getImage()->setAlt('Work with new technologies');
        $em->flush();

        if($request->isMethod('POST'))
        {
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');
            return $this->redirectToRoute('oc_platform_view', array('id' => $id));
        }
        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array('advert' => $advert));
    }

    // supprime toutes les catégories d'une annonce
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert)
        {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        foreach ($advert->getCategories() as $category)
        {
            $advert->removeCategory($category);
        }

        $em->flush();
        return $this->redirectToRoute('oc_platform_home');
    }

    // affiche les $limit dernières annonces
    public function menuAction($limit)
    {
        $listAdverts = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('OCPlatformBundle:Advert')
            ->findBy(
           array(),                 // Pas de critère
           array('date' => 'desc'), // On trie par date décroissante
           $limit,                  // On sélectionne $limit annonces
           0                        // À partir du premier
       )
        ;

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
            //Tout l'intérêt est ici : le contrôleur passe
            //les variables nécessaires au template !
            'listAdverts' => $listAdverts
        ));
    }


    public function testAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $advert = $em->getRepository('OCPlatformBundle:Advert')->findOneById($id);
        $test = new OCPurge($em);
        $age = $test->calculAge($advert);
        return $this->render('OCPlatformBundle:Advert:test.html.twig', array('id'=>$id, 'age'=>$age));

    }

    public function purgeAction($days, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $purge = new OCPurge($em);
        $purge->purge($days);
        $this->get('session')->getFlashBag()->set('notice', 'Les annonces sans candidature de plus de '.$days.' jours ont été supprimées.');
        return $this->redirectToRoute('oc_platform_home');
        //return $this->redirectToRoute('oc_platform_home');
    }

}