<?php

namespace OC\PlatformBundle\Form;

use OC\PlatformBundle\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvertType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pattern = 'D%';
        $builder
            ->add('date', DateTimeType::class, array('format' => 'dd-MM-yyyy'))// Le deuxième argument attend le nom de la classe du type utilisé
            ->add('title', TextType::class)// exemple : TextType::class équivaut à 'Symfony\Component\Form\Extension\Core\Type\TextType'
            ->add('content', TextareaType::class)// voir author pour l'autre possibilité :
            ->add('author', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            // ajout du formulaire pour l'image liée à l'annonce :
            ->add('image', ImageType::class)
            /* ajout du formulaire des catégories liées à l'annonce :
               * Rappel :
               ** - 1er argument : nom du champ, ici « categories », car c'est le nom de l'attribut
               ** - 2e argument : type du champ, ici « CollectionType » qui est une liste de quelque chose
               ** - 3e argument : tableau d'options du champ
            */
            ->add('categories', EntityType::class, array(
                'class' => 'OCPlatformBundle:Category',
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function (CategoryRepository $repository) use ($pattern) {
                    return $repository->getLikeQueryBuilder($pattern);
                }
            ))
            ->add('save', SubmitType::class);
        // Ajout d'une fonction qui va écouter un événement
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, // évènement qui nous intéresse : ici PRE_SET_DATA
            function (FormEvent $event) { // Fonction à exécuter lorsque l'évènement est déclenché
                $advert = $event->getData();

                if (null == $advert) {
                    return;
                }
                if (!$advert->getPublished() || null === $advert->getId()) {
                    $event->getForm()->add(
                        'published', CheckboxType::class, array(
                            'required' => false)
                    );
                } else {
                    $event->getForm()->remove('author');
                    $event->getForm()->remove('published');
                }
            }
        );

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'OC\PlatformBundle\Entity\Advert'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oc_platformbundle_advert';
    }


}
