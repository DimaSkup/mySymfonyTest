<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\PostRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class PostsController extends Controller
{
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
        $postRepository = $this->getDoctrine()->getRepository(Post::class);
        $page = intval($request->query->get('page', 1));
        $sortBy = $request->query->get('display_order');
        $resultPerPage = 25;
        $numPages = 0;

        if ($page === null)
        {
            $request->query->set('page', 1);
            $page = 1;
        }

        if ($sortBy === null)
        {
            $request->query->set('display_order', 'new');
            $sortBy = 'new';
        }



        $posts = $postRepository->findAllPaginated($numPages, $page, $resultPerPage);

        if ($sortBy === 'old')
        {
            $order = "ASC";
            $posts = $this->sortPostSetBy($posts, "created_at", $order);
        }
        else if ($sortBy === 'new')
        {
            $order = "DESC";
            $posts = $this->sortPostSetBy($posts, "created_at", $order);
        }
        else
        {
            $posts = $this->sortPostSetBy($posts, $sortBy);
        }

        return $this->render('posts/index.html.twig', [
            'posts' => $posts,
            'numPages' => $numPages,
            'curPage' => $page
        ]);
    }

    public function sortPostSetBy($postSetForSort, $sortBy, $order = "ASC")
    {
        if ($sortBy === "created_at")
        {
            if ($order === "DESC")
            {
                usort($postSetForSort, function($post1, $post2)
                {
                    if ($post1->getCreatedAt() == $post2->getCreatedAt()) return 0;
                    return ($post1->getCreatedAt() > $post2->getCreatedAt()) ? -1 : 1;
                });
            }
            else if ($order === "ASC")
            {
                usort($postSetForSort, function($post1, $post2)
                {
                    if ($post1->getCreatedAt() == $post2->getCreatedAt()) return 0;
                    return ($post1->getCreatedAt() < $post2->getCreatedAt()) ? -1 : 1;
                });
            }
        }
        else if ($sortBy === "username")
        {
            usort($postSetForSort, function($post1, $post2)
            {
                if ($post1->getUsername() == $post2->getUsername()) return 0;
                return ($post1->getUsername() < $post2->getUsername()) ? -1 : 1;
            });
        }
        else if ($sortBy === "email")
        {
            usort($postSetForSort, function($post1, $post2)
            {
                if ($post1->getEmail() == $post2->getEmail()) return 0;
                return ($post1->getEmail() < $post2->getEmail()) ? -1 : 1;
            });
        }

        return $postSetForSort;
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


            //return $this->redirectToRoute('blog_posts', ['page' => 1]);
            return $this->render('posts/message_аfter_сreate_post.html.twig');
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
    public function delete(Post $post, Request $request)
    {
        $currentPage = ($request->query->get('page'));
        $em = $this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('blog_posts', ['page' => $currentPage]);
    }


    /**
     *
     * @param Post $post
     * @return Response
     */
    public function showPostAndCreateNewComment(Post $post, Request $request)
    {
        // check if this post was created by the current user
        // if so, we will allow him to edit this post
        $currUser = $this->getUser();
        //dd($currUser);
        if ($currUser)      // if the user is authorized
        {
            $currUserEmail = $currUser->getEmail();  // authorized user's email
            $userPostEmail = $post->getEmail();         // the email of the user who created current post

            if ($userPostEmail === $currUserEmail)
                $postIsByCurrentUser = true;
            else
                $postIsByCurrentUser = false;
        }
        else
            $postIsByCurrentUser = false;



        if ($currUser)   // if the current post is created by this user, we'll create a comment form
        {

            $comment = new Comment();
            $em = $this->getDoctrine()->getManager();
            $form = $this->createForm(CommentType::class, $comment);

            $post->addComment($comment);
            $currUser->addComment($comment);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $em->persist($comment);
                $em->flush();

                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }

            $formView = $form->createView();
        }
        else
            $formView = null;




        return $this->render('posts/show.html.twig', [
            'post' => $post,
            'postIsByCurrentUser' => $postIsByCurrentUser,
            'comment' => $post->getComments(),
            'form' => $formView,
        ]);
    }


    /** @var PostRepository $postRepository */
    private $postRepository;

    /** @var Security */
    private $security;

}