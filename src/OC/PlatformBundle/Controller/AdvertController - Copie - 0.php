<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdvertController extends Controller
{

	// La route fait appel à OCPlatformBundle:Advert:view,
  	// on doit donc définir la méthode viewAction.
 	// On donne à cette méthode l'argument $id, pour
 	// correspondre au paramètre {id} de la route
 	public function viewAction___($id, Request $request)
 	{
        // On récupère notre paramètre tag
        $tag = $request->query->get('tag');
        return new Response("Affichage de l'annonce d'id : ".$id.", avec le tag : ".$tag);	
    }

    public function _viewAction___($id)
    {
        return $this->redirectToRoute('oc_platform_home');
    }

    public function __viewAction____($id)
    {
        return new JsonResponse(array('id' => $id));
    }

    public function ____viewAction___($id)
    {
        return $this->render(
            'OCPlatformBundle:Advert:view.html.twig',
            array('id'  => $id)
        );
    }

    public function viewAction($id, Request $request)
    {
        // Récupération de la session
        $session = $request->getSession();
    
        // On récupère le contenu de la variable user_id
        $userId = $session->get('user_id');

        // On définit une nouvelle valeur pour cette variable user_id
        $session->set('user_id', 91);
        
        return $this->render(
            'OCPlatformBundle:Advert:view.html.twig',
            array('id'  => $id)
        );
    }

    // On récupère tous les paramètres en arguments de la méthode
    public function viewSlugAction($slug, $year, $_format)
    {
        return new Response(
            "On pourrait afficher l'annonce correspondant au
            slug '".$slug."', créée en ".$year." et au format ".$_format."."
        );
    }
    

    public function indexAction()
    {
        //return new Response("Hello World !");


        //$content = $this->get('templating')->render('OCPlatformBundle:Advert:index.html.twig');


        //$content = $this
    			//->get('templating')
    			//->render('OCPlatformBundle:Advert:index.html.twig', array('nom' => 'Ba Staph'));
    	//return new Response($content);


        // On veut avoir l'URL de l'annonce d'id 5.
        $url = $this->get('router')->generate(
            'oc_platform_view', // 1er argument : le nom de la route
            array('id' => 5),    // 2e argument : les valeurs des paramètres
            true
        );
        // $url vaut « /platform/advert/5 »
        return new Response("L'URL de l'annonce d'id 5 est : ".$url);
    }
    
    public function addAction(Request $request)
    {
        $session = $request->getSession();
    
        // Bien sûr, cette méthode devra réellement ajouter l'annonce
    
        // Mais faisons comme si c'était le cas
        $session->getFlashBag()->add('info', 'Annonce bien enregistrée');

        // Le « flashBag » est ce qui contient les messages flash dans la session
        // Il peut bien sûr contenir plusieurs messages :
        $session->getFlashBag()->add('info', 'Oui oui, elle est bien enregistrée !');

        // Puis on redirige vers la page de visualisation de cette annonce
        return $this->redirectToRoute('oc_platform_view', array('id' => 5));
    }

}