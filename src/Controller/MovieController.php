<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieType;
use App\Movie\Enum\SearchType;
use App\Movie\Provider\MovieProvider;
use App\Repository\MovieRepository;
use App\Security\Voter\MovieUnderageVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/movie')]
class MovieController extends AbstractController
{
    #[Route('', name: 'app_movie_index')]
    public function index(Request $request, MovieRepository $repository, int $limit): Response
    {
        $page = $request->query->getInt('page', 1);
        $pageNums = ceil($repository->count() / $limit);
        $movies = $repository->findBy([], [], $limit, ($page - 1) * $limit);

        return $this->render('movie/index.html.twig', [
            'movies' => $movies,
            'pageNums' => $pageNums,
            'currentPage' => $page,
        ]);
    }

    #[IsGranted(MovieUnderageVoter::UNDERAGE, 'movie')]
    #[Route('/{id<\d+>}', name: 'app_movie_show')]
    public function show(?Movie $movie): Response
    {
        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
        ]);
    }

    #[Route('/omdb/{title}', name: 'app_movie_omdb', methods: ['GET'])]
    public function omdb(string $title, MovieProvider $provider): Response
    {
        $movie = $provider->getOne($title, SearchType::Title);
        $this->denyAccessUnlessGranted(MovieUnderageVoter::UNDERAGE, $movie);

        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
        ]);
    }

    #[Route('/new', name: 'app_movie_new', methods: ['GET', 'POST'])]
    #[Route('/{id<\d+>}/edit', name: 'app_movie_edit', methods: ['GET', 'POST'])]
    public function newMovie(Request $request, ?Movie $movie, EntityManagerInterface $manager): Response
    {
        $movie ??= new Movie();
        $form = $this->createForm(MovieType::class, $movie);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //$user = $this->getUser();
            //if ($user instanceof User && !$movie->getId()) {
            //    $movie->setCreatedBy($user);
            //}
            $manager->persist($movie);
            $manager->flush();

            return $this->redirectToRoute('app_movie_show', ['id' => $movie->getId()]);
        }

        return $this->render('movie/new.html.twig', [
            'form' => $form,
            'movie' => $movie,
        ]);
    }

    #[Route('/_decades', name: 'app_movie_decades')]
    public function decades(): Response
    {
        $decades = ['80', '90', '2000'];

        return $this->render('includes/_decades.html.twig', [
            'decades' => $decades
        ]);
    }
}
