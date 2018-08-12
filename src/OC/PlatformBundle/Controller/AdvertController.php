<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Form\AdvertType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{
    public function indexAction($page, Request $request)
    {

        if ($page < 1) {
            throw new NotFoundHttpException("Page '" . $page . "' n'existe pas.");

        }

        $em = $this->getDoctrine()->getManager();

        $perpage = $this->getParameter('paginator');
        $listAdverts = $em->getRepository('OCPlatformBundle:Advert')->getAdverts($page, $perpage);

        $nbPages = ceil(count($listAdverts) / $perpage);

        // test knpPaginatorBundle
        $listAdvertsPagine = $em->getRepository('OCPlatformBundle:Advert')->findAll();
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $listAdvertsPagine,
            $request->query->getInt('page', $page),
            4
        );

        if ($page > $nbPages && $page > $pagination) {
            throw new NotFoundHttpException("La page " . $page . " n'existe pas.");
        }


        // Et modifiez le 2nd argument pour injecter notre liste
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts,
            'nbPages' => $nbPages,
            'page' => $page,
            'pagination' => $pagination
        ));
    }

    public function viewAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        // On récupère le repository
        $advertRepository = $em->getRepository('OCPlatformBundle:Advert');

        // On récupère l'entité correspondante à l'id $id
        $advert = $advertRepository->find($id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id  n'existe pas, d'où ce if :
        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(
                array('advert' => $advert)
            );

        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(
                array('advert' => $advert)
            );


        // Le render ne change pas, on passait avant un tableau, maintenant un objet
        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert' => $advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills
        ));
    }

    public function addAction(Request $request)
    {

        $em = $this->getDoctrine()
            ->getManager();

        $advert = new Advert();
//        $form = $this->get('form.factory')->create(AdvertType::class, $advert); // Equivaut à :
        $form = $this->createForm(AdvertType::class, $advert);


        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            // Puis on redirige vers la page de visualisation de cette annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        //        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
            'form' => $form->createView()));
    }

    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository("OCPlatformBundle:Advert")->find($id);

        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce ayant l'id " . $id . " n'existe pas.");

        }

        // Même mécanisme que pour l'ajout
        if ($request->isMethod('POST')) {
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

            return $this->redirectToRoute('oc_platform_view', array('id' => 5));
        }

        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert
        ));
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce ayant l'id " . $id . " n'existe pas.");

        }

        foreach ($advert->getCategories() as $category) {
            $advert->removeCategory($category);

        }

        $em->flush();

        return $this->render('OCPlatformBundle:Advert:delete.html.twig');
    }

    public function menuAction($limit)
    {

        $em = $this->getDoctrine()->getManager();

        $listAdverts = $em->getRepository('OCPlatformBundle:Advert')->findBy(
            array(), // pas de critères de filtre
            array('date' => 'desc'), // on trie sur date décroissante
            $limit, // On sélectionne $limit annonce (declaré dans le layout.html.twig)
            0 // à partir du premier
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
            // Tout l'intérêt est ici : le contrôleur passe
            // les variables nécessaires au template !
            'listAdverts' => $listAdverts
        ));
    }

    public function editImageAction($advertId)
    {
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($advertId);

        // On modifie l'URL de l'image par exemple
        $advert->getImage()->setUrl('test.png');

        // On n'a pas besoin de persister l'annonce ni l'image.
        // Rappelez-vous, ces entités sont automatiquement persistées car
        // on les a récupérées depuis Doctrine lui-même

        // On déclenche la modification
        $em->flush();

        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert'=>$advert
        ));
    }

    public function myFindAllAction()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('OCPlatformBundle:Advert');

        // Méthode QueryBuilder
        $listAdverts = $repository->myFindAll();
        $oneAdvert = $repository->myFindOne(2);
        $findAdvert = $repository->myFindByAuthor('CESI');


        // Méthode Doctrine Query Language DQL
        $dqlAdverts = $repository->myFindAllDQL();


        // petit exercice d'apprentissage :
        $catune = 'Développement web';
        $catdeux = 'Développement mobile';
        $conditions = array($catune, $catdeux);

        // Utilisation de callback (mise à jour de updateAt à chaque modification de l'annonce)
        foreach ($oneAdvert as $advert) {
            $advert->setContent("Nous recherchons des développeurs informatique dans le cadre d'une alternance d'une durée de 2 ans afin de préparer un Master. Envoyez nous vos candidatures, début de la formation le 5 novembre 2018. (Exige le Bac+2) ");
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $advertByCat = $repository->getAdvertWithCategories($conditions);

        return $this->render('OCPlatformBundle:Advert:test.html.twig', array(
            'listAdverts' => $listAdverts,
            'oneAdvert' => $oneAdvert,
            'findAdvert' => $findAdvert,
            'dqlAdverts' => $dqlAdverts,
            'advertByCat' => $advertByCat,
            'conditions' => $conditions
        ));
    }

    public function testSlugAction()
    {
        // creation d'une nouvelle annonce contenant le title qui deviendra le Slug :
//        $advert = new Advert();
//        $advert->setAuthor("Sluger");
//        $advert->setContent("Un petit test avec l'extension Slugggable (stof_doctrine_extensions)");
//        $advert->setTitle('Test du Slug');
//        $em->persist($advert);
//        $em->flush();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('OCPlatformBundle:Advert');
        $advert = $repository->find(3);

        return new Response('Voici le résultat, avant tout le titre de l\'annonce : "' . $advert->getTitle() . '"" et le slug généré pour cette annonce : "' . $advert->getSlug() . '"');

    }

    public function formAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        // si on edit une annonce
        // Récupération d'une annonce déjà existante, d'id $id.
        $advert = $this->getDoctrine()
            ->getManager()
            ->getRepository('OCPlatformBundle:Advert')
            ->find($id);

// Et on construit le formBuilder avec cette instance de l'annonce, comme précédemment
        // sinon on ajoute une annonce

        $form = $this->get('form.factory')->createBuilder(FormType::class, $advert)
            ->add('date', DateType::class)// Le deuxième argument attend le nom de la classe du type utilisé
            ->add('title', TextType::class)// exemple : TextType::class équivaut à 'Symfony\Component\Form\Extension\Core\Type\TextType'
            ->add('content', TextareaType::class)// voir author pour l'autre possibilité :
            ->add('author', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            ->add('published', CheckboxType::class, array('required' => false))
            ->add('save', SubmitType::class)
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
            }

            // Puis on redirige vers la page de visualisation de cette annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }
//
//        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
            'form' => $form->createView()));
    }

}