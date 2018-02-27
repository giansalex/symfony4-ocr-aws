<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 14/02/2018
 * Time: 12:03 PM
 */

namespace App\Controller;

use Curl\Curl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Class TesseractController
 */
class TesseractController extends AbstractController
{
    const CPE_VALIDEZ_URL = 'http://www.sunat.gob.pe/ol-ti-itconsvalicpe/ConsValiCpe.htm';

    const CPE_CAPTCHA_URL = 'http://www.sunat.gob.pe/ol-ti-itconsvalicpe/captcha?accion=image&nmagic=0';

    public function index(Request $request): Response
    {
        $url = $request->query->get('url');
        if (empty($url))
        {
            return new Response('falta el parametro url en la query', 400);
        }

        $pathStore = sys_get_temp_dir() . '/captcha123.png';
        list($image, $cookies) = $this->getImageWithCookies($url);
        file_put_contents($pathStore, $image);
        $result = (new TesseractOCR($pathStore))
            ->whitelist(range('A', 'Z'))
            ->run();

        $cooks = $this->joinArray($cookies);
        $imgb4 = base64_encode($image);
        $html = <<<HTML
        <h3>Image</h3>
<img src="data:image/jpg;base64,{$imgb4}" alt="image"/>
<p><strong>Resultado:</strong> {$result}</p>
<div>
<strong>Cookies</strong><br>
{$cooks}
</div>
HTML;
        unlink($pathStore);
        return new Response($html, 200, ['Content-Type', 'text/html']);
    }

    private function getImageWithCookies($url)
    {
        $image = file_get_contents($url);
        $cookies = [];
        foreach ($http_response_header as $hdr) {
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
                parse_str($matches[1], $tmp);
                $cookies += $tmp;
            }
        }

        return [$image, $cookies];
    }

    public function consult(Request $request, Curl $curl)
    {
        $query = $request->query;
        list($captcha, $cookies) = $this->getCaptchaResult(self::CPE_CAPTCHA_URL);

        $params = [
            'accion' => 'CapturaCriterioValidez',
            'num_ruc' => $query->get('emisor'),
            'tipocomprobante' => $query->get('tipo_doc'),
            'cod_docide' => $query->get('tipo_client'),
            'num_docide' => $query->get('num_client'),
            'num_serie' => $query->get('serie'),
            'num_comprob' => $query->get('numero'),
            'fec_emision' => $query->get('fecha'),
            'cantidad' => $query->get('total'),
            'codigo' => $captcha,
        ];

        $curl->setUserAgent('');
        $curl->setCookies($cookies);
        $result = $curl->post(self::CPE_VALIDEZ_URL, $params);

        if ($result === false) {
            return new Response($curl->errorMessage);
        }

        return new Response($result, 200, ['Content-Type', 'text/html']);
    }

    private function joinArray($items)
    {
        $result = '';
        foreach ($items as $key => $value) {
            $result .= $key.'='.$value.'<br>';
        }

        return $result;
    }

    private function getCaptchaResult($url)
    {
        $pathStore = sys_get_temp_dir() . '/captcha123.png';
        list($image, $cookies) = $this->getImageWithCookies($url);
        file_put_contents($pathStore, $image);
        $result = (new TesseractOCR($pathStore))
            ->whitelist(range('A', 'Z'))
            ->run();

        unlink($pathStore);

        $result = preg_replace("[\s+]", '', $result);
        return [$result, $cookies];
    }
}