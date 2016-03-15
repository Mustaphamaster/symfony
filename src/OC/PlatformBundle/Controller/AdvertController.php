<?php
// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use OC\PlatformBundle\Form\AdvertType;
use OC\PlatformBundle\Form\AdvertEditType;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\Application;
use OC\PlatformBundle\Entity\AdvertSkill;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use OC\PlatformBundle\Bigbrother\BigbrotherEvents;
use OC\PlatformBundle\Bigbrother\MessagePostEvent;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class AdvertController extends Controller
{
  public function indexAction($page)
  {
    if ($page < 1) {
      throw $this->createNotFoundException("La page ".$page." n'existe pas.");
    }

    // Pour récupérer la liste de toutes les annonces : on utilise findAll()
    // $listAdverts = $this->getDoctrine()
    //   ->getManager()
    //   ->getRepository('OCPlatformBundle:Advert')
    //   ->findAll()
    // ;
    $nbPerPage = 3;
    $listAdverts = $this->getDoctrine()
      ->getManager()
      ->getRepository('OCPlatformBundle:Advert')
      ->getAdverts($page, $nbPerPage);

    // On calcule le nombre total de pages grâce au count($listAdverts) qui retourne le nombre total d'annonces
    $nbPages = ceil(count($listAdverts)/$nbPerPage);

    // L'appel de la vue ne change pas
    return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
      'listAdverts' => $listAdverts,
      'nbPages' => $nbPages,
      'page' => $page
    ));
  }

  public function viewAction(Advert $advert, $id)
  {
    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // Pour récupérer une annonce unique : on utilise find()
    //$advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);
    //$advert = $em->find('OC\PlatformBundle\Entity\Advert', $id);

    // On vérifie que l'annonce avec cet id existe bien
    //if ($advert === null) {
      //throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
    //}

    // On récupère la liste des advertSkill pour l'annonce $advert
    $listAdvertSkills = $em->getRepository('OCPlatformBundle:AdvertSkill')->findByAdvert($advert);

    // Puis modifiez la ligne du render comme ceci, pour prendre en compte les variables :
    return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
      'advert'           => $advert,
      'listAdvertSkills' => $listAdvertSkills,
    ));
  }

  //**
   //* @Security("has_role('ROLE_AUTEUR')")
   //*/ 
  public function addAction(Request $request)
  {
    // La gestion d'un formulaire est particulière, mais l'idée est la suivante :

    // On vérifie que l'utilisateur dispose bien du rôle ROLE_AUTEUR
    if (!$this->get('security.context')->isGranted('ROLE_AUTEUR')) {
      // Sinon on déclenche une exception « Accès interdit »
      // throw new AccessDeniedException('Accès limité aux auteurs.');
    }

    $advert = new Advert();
    //$form = $this->get('form.factory')->create(new AdvertType(), $advert);
    $form = $this->createForm(new AdvertType(), $advert);

    if ($request->isMethod('POST')) {

      if ($form->handleRequest($request)->isValid()) {
        // On l'enregistre notre objet $advert dans la base de données, par exemple
        $em = $this->getDoctrine()->getManager();
        $advert->setUser($this->getUser());

        // On crée l'évènement avec ses 2 arguments
        $event = new MessagePostEvent($advert->getContent(), $advert->getUser());

        // On déclenche l'évènement
        $this
          ->get('event_dispatcher')
          ->dispatch(BigbrotherEvents::onMessagePost, $event)
        ;

        // On récupère ce qui a été modifié par le ou les listeners, ici le message
        $advert->setContent($event->getMessage());

        // On récupère toutes les compétences possibles
        $listSkills = $em->getRepository('OCPlatformBundle:Skill')->findAll();
        // Pour chaque compétence
        foreach ($listSkills as $skill) {
          // On crée une nouvelle « relation entre 1 annonce et 1 compétence »
          $advertSkill = new AdvertSkill();

          // On la lie à l'annonce, qui est ici toujours la même
          $advertSkill->setAdvert($advert);
          // On la lie à la compétence, qui change ici dans la boucle foreach
          $advertSkill->setSkill($skill);

          // Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
          $advertSkill->setLevel('Expert');

          // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
          //$em->persist($advertSkill); ***************
        }

        // Création d'une première candidature
        $application1 = new Application();
        $application1->setAuthor('Mustapha');
        $application1->setContent("J'ai toutes les qualités requises.");

        // Création d'une deuxième candidature par exemple
        $application2 = new Application();
        $application2->setAuthor('Yassine');
        $application2->setContent("Je suis très motivé.");

        // On lie les candidatures à l'annonce
        $application1->setAdvert($advert);
        $application2->setAdvert($advert);

        $em->persist($advert);

        //$em->persist($application1);***************
        //$em->persist($application2);***************

        $em->flush();

        $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

        // On redirige vers la page de visualisation de l'annonce nouvellement créée
        return $this->redirect($this->generateUrl('oc_platform_view', array('id' => $advert->getId())));
      }

    }

    // On passe la méthode createView() du formulaire à la vue
    // afin qu'elle puisse afficher le formulaire toute seule
    return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
      'form' => $form->createView()
    ));
  }

  public function editAction(Request $request, $id)
  {
    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // On récupère l'entité correspondant à l'id $id
    $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);
    
    // Si l'annonce n'existe pas, on affiche une erreur 404
    if ($advert == null) {
      throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
    }

    $form = $this->createForm(new AdvertEditType(), $advert);
    
    if ($request->isMethod('POST')) {
      if ($form->handleRequest($request)->isValid()) {
        
        //$em->persist($advert);
        $em->flush();
        $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

        // On redirige vers la page de visualisation de l'annonce modifié
        return $this->redirect($this->generateUrl('oc_platform_view', array('id' => $advert->getId())));
      }
    }

    return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
      'advert' => $advert,
      'form' => $form->createView()
    ));
  }

  public function deleteAction($id, Request $request)
  {
    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // On récupère l'entité correspondant à l'id $id
    $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

    // Si l'annonce n'existe pas, on affiche une erreur 404
    if ($advert == null) {
      throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
    }

    $form = $this->createFormBuilder()->getForm();

    if ($request->isMethod('POST')) {
      if ($form->handleRequest($request)->isValid()) {
        // Si la requête est en POST, on deletea l'article
        $em->remove($advert);
        $em->flush();

        $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

        // Puis on redirige vers l'accueil
        return $this->redirect($this->generateUrl('oc_platform_home'));
      }
    }

    // Si la requête est en GET, on affiche une page de confirmation avant de delete
    return $this->render('OCPlatformBundle:Advert:delete.html.twig', array(
      'advert' => $advert,
      'form'   => $form->createView()
    ));
  }

  public function menuAction($limit = 3)
  {
    $listAdverts = $this->getDoctrine()
      ->getManager()
      ->getRepository('OCPlatformBundle:Advert')
      ->findBy(
        array(),                 // Pas de critère
        array('date' => 'desc'), // On trie par date décroissante
        $limit,                  // On sélectionne $limit annonces
        0                        // À partir du premier
    );

    return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
      'listAdverts' => $listAdverts
    ));
  }

  public function translationAction($name)
  {
    return $this->render('OCPlatformBundle:Advert:translation.html.twig', array(
      'name' => $name
    ));
  }

  /**
   * @ParamConverter("json")
   */
  public function ParamConverterAction($json)
  {
    return new Response(print_r($json, true));
  }

}