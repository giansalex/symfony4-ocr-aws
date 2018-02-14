<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 14/02/2018
 * Time: 12:01 PM
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DefaultController
 */
class DefaultController extends AbstractController
{
    private $router;

    /**
     * DefaultController constructor.
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function index(): Response
    {
        $link = $this->router->generate('tesseract_index');
        $html = <<<HTML
<h1>Symfony 4</h1>
<a href="{$link}">Tesseract ocr</a>
HTML;

        return new Response($html);
    }
}