<?php

// src/OC/CoreBundle/Controller/CoreController.php

namespace OC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CoreController extends Controller
{
    public function indexAction()
    {
        return $this->render('OCCoreBundle:Core:index.html.twig');
    }
}