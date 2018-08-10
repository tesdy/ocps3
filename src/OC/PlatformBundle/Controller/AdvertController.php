<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{
    public function indexAction($page)
    {

        if ($page < 1) {
            throw new NotFoundHttpException("Page '" . $page . "' n'existe pas.");

        }

        $listAdverts = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert')->getAdverts();


        // Et modifiez le 2nd argument pour injecter notre liste
        return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts
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

        if ($request->isMethod('POST')) {
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            // Puis on redirige vers la page de visualisation de cette annonce
            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }
//
//        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig');
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




}