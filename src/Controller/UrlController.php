<?php

namespace App\Controller;

use App\Entity\Url;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UrlController extends Controller
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
        $this->logger->debug("Trying create new url " . $protocol . $address);

        $url = new Url();
        $url->setValue($protocol . $address);

        $this->logger->debug("Validation of url");
        $errors = $validator->validate($url);

        $availableProtocols = [
            "http://",
            "https://"
        ];

        if (!in_array($protocol, $availableProtocols)) {
            $this->logger->warning("User tried to shorten URL with unavailable protocol " . $protocol . ", but was stopped by system (may be hacked attempt)");
            $message = "You can shorten url only with available protocols";
            $errors->add(new ConstraintViolation($message, $message, [], '', '', null));
        }
        if (!strpos($address, ".")) {
            $this->logger->info("User tried to shorten URL with domain level less than 2, but was stopped by system");
            $message = "You can shorten url with domain level not less than 2";
            $errors->add(new ConstraintViolation($message, $message, [], '', '', null));
        }
        if (substr($address, -1) === ".") {
            $this->logger->info("User tried to shorten URL with root domain, but was stopped by system");
            $message = "Please, don't use root domain in url";
            $errors->add(new ConstraintViolation($message, $message, [], '', '', null));
        }

        if (count($errors) > 0) {
            $this->addFlash('error', $errors->get(0)->getMessage());
            return $this->redirect($this->generateUrl('url_index'));
        }

        $this->logger->debug("Trying to create row at db");
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($url);
        $entityManager->flush();
        if ($url->getId()) {
            $this->logger->debug("Row successfully created");
        } else {
            $this->logger->error("Error of inserting url " . $url->getValue() . " to db");
            throw new Exception("Can't add url " . $url->getValue() . " to db");
        }
        $this->logger->debug("Start generation of url");
        $shortedUrl = $this->generateUrl('url_go', ['key' => self::convertIntToKey($url->getId())], UrlGenerator::ABSOLUTE_URL);

        $this->logger->debug("Return shorted url to user");
        $this->logger->info("User shorted URL " . $url->getValue() . " to " . $shortedUrl);
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
