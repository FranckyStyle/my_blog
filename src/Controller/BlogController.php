<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\HttpFoundation\Request;  
 



class BlogController extends AbstractController
{
   
    public function index(): Response
    {
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(
            ['isPublished' => true],
            ['publicationDate' => 'desc']
        );

        return $this->render('blog/index.html.twig', ['articles' => $articles]);
    }


    public function add(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
                
        $article = new Article(); 
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
                $article->setLastUpdateDate(new \DateTime());

                if ($article->getPicture() !== null) {
                    $file = $form->get('picture')->getData();
                    $fileName =  uniqid(). '.' .$file->guessExtension();
    
                    try {
                        $file->move(
                            $this->getParameter('images_directory'), // Le dossier dans lequel le fichier va être charger
                            $fileName
                        );
                    } catch (FileException $e) {
                        return new Response($e->getMessage());
                    }
    
                    $article->setPicture($fileName);
                }
                
                if ($article->getIsPublished()) {
                    $article->setPublicationDate(new \DateTime());
            }

            $em = $this->getDoctrine()->getManager(); // On récupère l'entity manager
                $em->persist($article); // On confie notre entité; l'entity manager (on persist l'entité)
                $em->flush(); // On execute la requete

                return $this->redirectToRoute('admin');
        }

        return $this->render('blog/add.html.twig', [
            'form' => $form->createView()
        ]);
    }


    public function show(Article $article)
    {
        return $this->render('blog/show.html.twig', [
            'article' => $article
        ]);
    }


    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Article $article, Request $request)
    { 
        $oldPicture = $article->getPicture();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setLastUpdateDate(new \DateTime());

            if ($article->getIsPublished()) {
                $article->setPublicationDate(new \DateTime());
            }

            if ($article->getPicture() !== null && $article->getPicture() !== $oldPicture) {
                $file = $form->get('picture')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $article->setPicture($fileName);
            } else {
                $article->setPicture($oldPicture);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('admin');
        }

        return $this->render('blog/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView() 
        ]);
    }

    public function remove(Article $article)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();

        return $this->redirectToRoute('admin');
    }


    public function admin()
    {
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(
            [],
            ['lastUpdateDate' => 'DESC']
        );

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users
        ]);
    }

}
