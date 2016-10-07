<?php

namespace OC\PlatformBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use OC\PlatformBundle\Repository\CategoryRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AdvertType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Artbitrairement, on récupère toutes les catégoriess qui commencent par "D"
        $pattern = 'D%';

        $builder
            ->add('date', DateType::class)
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('author', TextType::class)
            ->add('published', CheckboxType::class, array('required' => false))
            ->add('email', EmailType::class)
            ->add('image', ImageType::class)
            ->add('categories', EntityType::class, array(
                'class' => 'OCPlatformBundle:Category',
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function(CategoryRepository $repository) use($pattern)
                {
                    return $repository->getLikeQueryBuilder($pattern);
                }
            ))
            ->add('save', SubmitType::class)
        ;

        $builder->addEventListener(
         FormEvents::PRE_SET_DATA, // 1er argument: l'évènement qui nous intéresse: ici, PRE_SET_DATA
            function(FormEvent $event) // 2e argument : la fonction a exécuter lorsque l'évènement est déclenché
            {
                // On récupère notre objet Advert sous-jacent
                $advert = $event->getData();

                // Cette condition est importante, on en reparle plus loin
                if (null == $advert)
                {
                    return; // On sort de la fonction sans rien faire lorsque $advert vaut null
                }

                // Si l'annonce n'est pas publiée, ou si elle n'existe pas encore en base (id est null)
                if(!$advert->getPublished() || null === $advert->getId())
                {
                    // Alors on rajoute le champ published
                    $event->getForm()->add('published', CheckboxType::class, array('required' => false));
                }
                //else
                //{
                    // Sinon, on le supprime
                 //   $event->getForm()->remove('published');
                //}
            }
        );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'OC\PlatformBundle\Entity\Advert'
        ));
    }
}
