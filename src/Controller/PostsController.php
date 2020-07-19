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
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

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


    public function posts(Request $request)
    {
        $posts = $this->postRepository->findBy(['is_moderated' => true]);
        //dd($posts);
        $page = $request->query->get('page');
        $postsPerPage = 25;
        $postsForPage[] = array();
        $firstPostNumForCurPage = ($page - 1) * $postsPerPage;

        for ($i = $firstPostNumForCurPage; $i < $firstPostNumForCurPage + $postsPerPage; $i++)
        {
            $postsForPage[] = $posts[$i];
        }
        //dd($postsForPage);
        return $this->render('posts/index.html.twig', [
            'posts' => $postsForPage
        ]);
    }



    /**
     *
     * @param Request $request
     * @param Slugify $slugify
     * @return Response
     */
    public function addPost(Request $request, Slugify $slugify)
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post, ['images_directory' => $this->getParameter('images_directory')]);
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid())
        {
            // ReCaptcha handling
            if (!$_POST['g-recaptcha-response'])
                exit('Please, fill the ReCaptcha');

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $key = '6Lft7qwZAAAAAMTUH3WFuGV18ekY3y3U4_VP3fvB';
            $query = $url.'?secret='.$key.'&response='.$_POST['g-recaptcha-response'].'&remoteip='.$_SERVER['REMOTE_ADDR'];
            $data = json_decode(file_get_contents($query));

            if ($data->success == false)
                exit("Captcha was inputted incorrectly. Please, try again");


            // File upload handling
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();


            // this condition is needed because the 'image' field is not required
            // so the JPG|PNG|GIF file must be processed only when a file is uploaded
            if (is_object($imageFile))
            {
                //$originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
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

                // updates the 'image' property to store the JPG|PNG|GIF file name
                // instead of its contents
                $post->setImage($newFilename);
            }
            else
            {
                $post->setImage(null);      // no image is attached to the post
            }


            // set the slug for the URL of this post
            $post->setSlug($slugify->slugify(substr($post->getText(), 0, 20)));     // the first 20 characters of the text will be a slug
            // set the user who created this post
            $post->setUser($user);
            // the post won't be displayed until it is moderated
            $post->setIsModerated(false);

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
     *
     * @param Request $request
     * @return Response
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
     *
     * @param Post $post
     * @param Request $request
     * @param Slugify $slugify
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit(Post $post, Request $request, Slugify $slugify)
    {

        $form = $this->createForm(PostType::class, $post, ['images_directory' => $this->getParameter('images_directory')]);
        $em = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // File upload handling
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();


            // this condition is needed because the 'image' field is not required
            // so the JPG|PNG|GIF file must be processed only when a file is uploaded
            if (is_object($imageFile))      // if we get a class UploadedFile object
            {
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

                // updates the 'image' property to store the JPG|PNG|GIF file name
                // instead of its contents
                $post->setImage($newFilename);
            }
            // this means that no image has been transferred to the form,
            // use an image that was previously attached to the post
            if ('default_image.png' !== basename($post->getImage()))
            {
                $post->setImage(basename($post->getImage()));
            }
            else        // no image was submitted with the form and no image has been attached to the post before
                $post->setImage(null);

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
     *
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Post $post)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('blog_posts');
    }


    /**
     *
     * @param Post $post
     * @return Response
     */
    public function post(Post $post)
    {
        $user = $this->getUser();

        // check if this post was created by the current user
        // if so, we will allow him to edit this post
        if ($post->getUser() === $user)
            $postIsByCurrentUser = true;
        else
            $postIsByCurrentUser = false;

        return $this->render('posts/show.html.twig', [
            'post' => $post,
            'postIsByCurrentUser' => $postIsByCurrentUser
        ]);
    }

    /*
     * @Route("/trans_example", name="trans_example")
     *
    public function transExample(Environment $twig, Request $request)
    {
        $response = new Response();
        //$str_trans = $translator->trans('Book is great');
        $str_trans = "Symfony is great";
        $template = $twig->render('trans_example.html.twig', [
            'text' => $str_trans
        ]);
        $response->setContent($template);
        return $response;
    }
    */
}