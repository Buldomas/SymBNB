<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Form\AdType;
use App\Repository\AdRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdController extends AbstractController
{
    /**
     * @Route("/ads", name="ads_index")
     */
    public function index(AdRepository $repo)
    {
        $ads = $repo->findAll();

        return $this->render('ad/index.html.twig', [
            'ads' => $ads,
        ]);
    }
    /**
     * Permet de créer une annonce
     * (A positionner avant le slug)
     * @Route("/ads/new", name="ads_create")
     * Vérification si le user est connécté
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function create(Request $request, ManagerRegistry $managerRegistry)
    {
        $ad = new Ad();

        $form = $this->createform(AdType::class, $ad);
        $form->handleRequest($request);

        /* Si soumis et valide */
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $managerRegistry->getManager();

            foreach ($ad->getImages() as $image) {
                $image->setAd($ad);
                $manager->persist($image);
            }
            $ad->setAuthor($this->getUser());
            $manager->persist($ad);
            $manager->flush();

            /* success peut être changé mais ici il correspond aux couleurs du Bootstrap */
            $this->addFlash(
                'success',
                "L'annonce <strong>{$ad->getTitle()}</strong> a bien été enregistrée !"
            );

            return $this->redirectToRoute("ads_show", [
                'slug' => $ad->getSlug()
            ]);
        }
        return $this->render('ad/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
    /**
     * Permet d'afficher le formulaire à modifier
     *
     * @Route("/ads/{slug}/edit", name="ads_edit")
     * Vérifie si connecté et bon utilisateur
     * @Security("is_granted('ROLE_USER') and user === ad.getAuthor()", message = "Cette annonce ne vous appartient pas et vous ne pouvez donc pas la modifier !")
     * 
     * @return Response
     */
    public function edit(Ad $ad, Request $request, ManagerRegistry $managerRegistry)
    {
        $form = $this->createform(AdType::class, $ad);
        $form->handleRequest($request);
        /* Si soumis et valide */
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $managerRegistry->getManager();

            foreach ($ad->getImages() as $image) {
                $image->setAd($ad);
                $manager->persist($image);
            }
            $manager->persist($ad);
            $manager->flush();

            /* success peut être changé mais ici il correspond aux couleurs du Bootstrap */
            $this->addFlash(
                'success',
                "Les modifications de <strong>{$ad->getTitle()}</strong> ont bien été enregistrées !"
            );

            return $this->redirectToRoute("ads_show", [
                'slug' => $ad->getSlug()
            ]);
        }
        return $this->render('ad/edit.html.twig', [
            'form' => $form->createView(),
            'ad' => $ad
        ]);
    }

    /**
     * Permet d'afficher une seule annonce
     *
     * @Route("/ads/{slug}", name="ads_show")
     * 
     * @return Response
     */
    /*public function show($slug, AdRepository $repo)*/
    public function show(Ad $ad)
    {
        //$ad = $repo->findOneBySlug($slug);
        return $this->render('ad/show.html.twig', [
            'ad' => $ad
        ]);
    }

    /**
     * Permet de supprimer une annonce
     *
     * @Route("/ads/{slug}/delete", name="ads_delete")
     * Vérifie si connecté et bon utilisateur
     * @Security("is_granted('ROLE_USER') and user === ad.getAuthor()", message = "Cette annonce ne vous appartient pas et vous ne pouvez donc pas la supprimer !")
     * 
     * @param Ad $ad
     * @param ManagerRegistry $managerRegistry
     * @return Response
     */
    public function delete(Ad $ad, ManagerRegistry $managerRegistry)
    {
        $titreAnnonce = $ad->getTitle();
        $manager = $managerRegistry->getManager();

        $manager->remove($ad);
        $manager->flush();

        /* success peut être changé mais ici il correspond aux couleurs du Bootstrap */
        $this->addFlash(
            'success',
            "La suppression de l'annonce <strong>{$titreAnnonce}</strong> a été effectuée !"
        );
        return $this->redirectToRoute("ads_index");
    }
}
