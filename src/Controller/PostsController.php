<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Security\Core\Security;

class PostsController extends AbstractController
{
    /** @var PostRepository $postRepository */
    private $postRepository;

    /** @var Security */
    private $security;

    /**
     * PostsController constructor.
     * @param PostRepository $postRepository
     * @param Security $security
     */
    public function __construct(PostRepository $postRepository, Security $security)
    {
        $this->postRepository = $postRepository;
        $this->security = $security;
    }

    /**
     * @Route("/", name="default_address")
     */
    public function index()
    {
        return $this->redirectToRoute("blog_posts");
    }

    /**
     * @Route("/posts", name="blog_posts")
     */
    public function posts()
    {
        $posts = $this->postRepository->findAll();

        return $this->render('posts/index.html.twig', [
            'posts' => $posts
        ]);
    }



    /**
     * @Route("/posts/new", name="new_blog_post")
     * @param Request $request
     * @param Slugify $slugify
     * @return Response
     */
    public function addPost(Request $request, Slugify $slugify)
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid())
        {
            if (!$_POST['g-recaptcha-response'])
                exit('Please, fill the ReCaptcha');

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $key = '6Lft7qwZAAAAAMTUH3WFuGV18ekY3y3U4_VP3fvB';
            $query = $url.'?secret='.$key.'&response='.$_POST['g-recaptcha-response'].'&remoteip='.$_SERVER['REMOTE_ADDR'];
            $data = json_decode(file_get_contents($query));

            if ($data->success == false)
                exit("Captcha was inputted incorrectly. Please, try again");

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'image' field is not required
            // son the JPG|PNG|GIF file must be processed only when a file is uploaded
            if ($imageFile)
            {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL

                //$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                //$newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $newFilename = md5(uniqid()).'.'.$imageFile->guessExtension();

                // Move the file to the directory where images are stored
                try
                {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                }
                catch (FileException $e)
                {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'imageFilename' property to store the JPG|PNG|GIF file name
                // instead of its contents
                $post->setImage($newFilename);
            }

            // set the slug for the URL of this post
            $post->setSlug($slugify->slugify(substr($post->getText(), 0, 20)));     // the first 20 characters of the text will be a slug
            // set the user who created this post
            $post->setUser($user);

            // save this post in the database
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('blog_posts');
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/posts/search", name="blog_search")
     */
    public function search(Request $request)
    {
        $query = $request->query->get('q');
        $posts = $this->postRepository->searchByQuery($query);

        return $this->render('posts/query_post.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @Route("/posts/{slug}/edit", name="blog_post_edit")
     */
    public function edit(Post $post, Request $request, Slugify $slugify)
    {
        $form = $this->createForm(PostType::class, $post);
        $em = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $post->setSlug($slugify->slugify(substr($post->getText(), 0, 20)));
            $em->flush();

            return $this->redirectToRoute('blog_show', [
                'slug' => $post->getSlug()
            ]);
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/posts/{slug}/delete", name="blog_post_delete")
     */
    public function delete(Post $post)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('blog_posts');
    }


    /**
     * @Route("/posts/{slug}", name="blog_show")
     */
    public function post(Post $post)
    {
        return $this->render('posts/show.html.twig', [
            'post' => $post
        ]);
    }
}