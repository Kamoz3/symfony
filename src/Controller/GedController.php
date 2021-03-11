<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Document;
use App\Entity\Genre;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use DateTime;

class GedController extends AbstractController
{
    /**
     * @Route("/uploadGed", name="uploadGed")
     */
    public function uploadGed(Request $request, EntityManagerInterface $manager): Response
    {
        $listeGenre = $manager->getRepository(Genre::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
            'controller_name' => "Upload d'un document",
            'listeGenre' => $listeGenre,
        ]);
    }

    /**
     * @Route("/insertGed", name="insertGed")
     */
    public function insertGed(Request $request, EntityManagerInterface $manager): Response
    {
        //Création d'un nouveau document
        $Document = new Document();
        //Récupération et transfert du fichier
        $brochureFile = $request->files->get("fichier");
        if ($brochureFile){
            $newFilename = uniqid('', true) . "." .
            $brochureFile->getClientOriginalExtension();
            $brochureFile->move($this->getParameter('upload'), $newFilename);
            //Insertion du document dans une base
            if($request->request->get('choix') == NULL){
                $actif = -1;
            } else{
                $actif=  1;
            } 
            $Document->setActif($actif);
            $Document->setNom($request->request->get('nom'));
            $Document->setTypeId($manager->getRepository(Genre::class)->findOneById($request->request->get('genre')));
            $Document->setCreatedAt(new \Datetime);
            $Document->setChemin($newFilename);
    
            $manager->persist($Document);
            $manager->flush();
        }

        $listeGenre = $manager->getRepository(Genre::class)->findAll();
        return $this->render('ged/uploadGed.html.twig', [
            'controller_name' => "Upload d'un document",
            'listeGenre' => $listeGenre,
        ]);
    }

    /**    
     * @Route("/listeGed", name="listeGed")
     */
    public function listeGed(Request $request, EntityManagerInterface $manager): Response
    {
		//Requête pour récupérer toute la table Document
		$listeGed = $manager->getRepository(Document::class)->findAll();

        return $this->render('ged/listeGed.html.twig', [
            'controller_name' => 'Liste des documents',
            'listeGed' => $listeGed,
        ]);
    }

    /**    
     * @Route("/deleteGed/{id}", name="deleteGed")
     */
    public function deleteGed(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
		//Suppression de l'objet avec l'id passé en paramètre
		$manager->remove($id);
        $manager->flush();

        return $this->redirectToRoute('listeGed');
    }

    /**    
     * @Route("/updateGed/{id}", name="updateGed")
     */
    public function updateGed(Request $request, EntityManagerInterface $manager, Document $id): Response
    {
        $sess = $request->getSession();
        //Créer des variables de ssions
        $sess->set("idGedModif", $id->getId());

        return $this->render('ged/updateGed.html.twig', [
            'controller_name' => "Mise à jour d'un genre",
            'ged' => $id,
        ]);
    }

    /**    
     * @Route("/updateGedBdd", name="updateGedBdd")
     */
    public function updateGedBdd(Request $request, EntityManagerInterface $manager): Response
    {
        $sess = $request->getSession();
        //Créer des variables de session
        $id = $sess->get("idGedModif");
        $ged = $manager->getRepository(Document::class)->findOneById($id);
        if(!empty($request->request->get('chemin')))
            $ged->setChemin($request->request->get('chemin'));
        if(!empty($request->request->get('actif')))
            $ged->setActif($request->request->get('actif'));
        if(!empty($request->request->get('nom')))
            $ged->setNom($request->request->get('nom'));
        $manager->persist($ged);
        $manager->flush();

        return $this->redirectToRoute('listeGed');
    }
}