<?php

namespace OC\PlatformBundle\Purge;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Repository\AdvertRepository;

class OCPurge
{


    public function purge($days)
    {
        $repository = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert');
        $listAdverts = $repository->findByNbApplications(0);

        ;
    }
}