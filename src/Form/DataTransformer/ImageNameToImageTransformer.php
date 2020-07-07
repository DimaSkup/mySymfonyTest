<?php
// src/Form/DataTransformer/ImageNameToImageTransformer.php

namespace App\Form\DataTransformer;

use App\Entity\Post;
use Composer\Semver\Constraint\EmptyConstraint;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Form\Exception\TransformationFailedException;

class ImageNameToImageTransformer implements DataTransformerInterface
{

    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transform an object (image) to a string (image filename).
     *
     * @param string|null $imageFilename
     * @return File
     */
    public function transform($imageFilename)
    {

        if (null === $imageFilename)
            dd("LOL");

        return $imageFilename;
    }

    /**
     * Transforms a string (image filename) to an object (File)
     *
     * @param File $imageFile
     * @return string|null
     * @throws TransformationfailedException is object (File) is not found.
     */
    public function reverseTransform($imageFile)
    {
        dd($imageFile);
        // no image filename? It's optional, so that's ok
        if (!$imageFile)
        {
            return '';
        }

     /*
        //$filePath = $this->getConfigurationPool()->getContainer()->getParameter('images_directory')."/".$imageFilename;
        $filePath = '%kernel.project_dir%/public/uploads/images' . '/' . $imageFilename;

        // transform the object to a string (the image to an image name)
        $imageFile = new File($filePath);

        if (null === $imageFile)
        {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An object with name "%s" doen\'t exits!',
                $imageFilename
            ));
        }
*/
        return $imageFile->getFilename();
    }
}