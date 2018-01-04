<?php

namespace App\Controller;

use App\Entity\Url;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UrlController extends Controller
{
    /**
     * @Route("/", name="url_index")
     */
    public function index()
    {
        return $this->render('index.html.twig', ['title' => 'Urlx']);
    }

    /**
     * @Route("/url", name="url_add")
     * @Method("POST")
     */
    public function add(Request $request, ValidatorInterface $validator)
    {
        $protocol = $request->request->get('protocol');
        $address = $request->request->get('url');

        $url = new Url();
        $url->setValue($protocol . $address);

        $errors = $validator->validate($url);

        if (count($errors) > 0) {
            $this->addFlash('error', $errors->get(0)->getMessage());
            return $this->redirect($this->generateUrl('url_index'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($url);
        $entityManager->flush();
        $shortedUrl = $this->generateUrl('url_go', ['key' => self::convertIntToKey($url->getId())], UrlGenerator::ABSOLUTE_URL);

        return $this->render('url.html.twig', ['title' => 'Urlx', 'url' => $shortedUrl]);
    }

    /**
     * @Route("/g/{key}", name="url_go", requirements={"key"="[a-zA-Z_-]+"})
     */
    public function go($key)
    {
        $id = self::convertKeyToInt($key);
        $url = $this->getDoctrine()->getRepository(Url::class)->find($id);

        if (!$url) {
            throw $this->createNotFoundException("Url not found");
        }

        return $this->redirect($url->getValue());
    }

    /**
     * Convert number to key
     * @param integer $number
     * @return string
     */
    public static function convertIntToKey($number)
    {
        $letters = self::getKeyLetters();
        $key = '';

        if ($number <= 0) {
            throw new Exception('Id must be greater than 0');
        }

        while ($number) {
            $index = $number % (count($letters) / 2);
            $key = $letters[$index] . $key;
            $number = (integer)($number / (count($letters) / 2));
        }

        return $key;
    }

    /**
     * Convert key to number
     * @param string $key
     * @return integer
     */
    public static function convertKeyToInt($key)
    {
        $letters = self::getKeyLetters();
        $number = 0;

        for($i = 0; $i < strlen($key); $i++)
        {
            $number += pow(count($letters) / 2, strlen($key) - $i - 1) * $letters[$key[$i]];
        }

        return $number;
    }

    public static function getKeyLetters()
    {
        $letters = [];
        $incrementer = 0;

        for ($i = 65; $i < 91; $i++) {
            $letters[$incrementer++] = chr($i);
            $letters[$incrementer++] = mb_strtolower(chr($i));
        }
        $letters[$incrementer++] = '_';
        $letters[$incrementer] = '-';

        $lettersSize = count($letters);
        for($i = 0; $i < $lettersSize; $i++)
        {
            $letters[$letters[$i]] = $i;
        }

        return $letters;
    }
}
