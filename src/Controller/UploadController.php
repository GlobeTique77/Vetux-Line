<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use App\Service\FusionService;

class UploadController extends AbstractController
{
    /**
     * @Route("/doUpload", name="do-upload")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, string $uploadDir,
                          FileUploader $uploader, LoggerInterface $logger): Response
    {
        $token = $request->get("token");

        if (!$this->isCsrfTokenValid('upload', $token))
        {
            $logger->info("CSRF failure");

            return new Response("Operation not allowed",  Response::HTTP_BAD_REQUEST,
                ['content-type' => 'text/plain']);
        }

        $files = $request->files->get('myfile');

        if (empty($files))
        {

            $this->addFlash('notice', 'No file specified');
            return $this->render('home/fusion.html.twig');
            //ancienement:
            //return new Response("No file specified",
            //    Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }
        foreach ($files as $file)
        {
            $filetype = $file->getMimeType();

            $filename = $file->getClientOriginalName();
            $uploader->upload($uploadDir, $file, $filename);
        }
        $this->addFlash('notice', 'Files uploaded');
        return $this->render('home/fusion.html.twig');
        //ancienemnt:
        //return new Response("File uploaded",  Response::HTTP_OK,
        //    ['content-type' => 'text/plain']);
    }

    /**
     * @Route("/fsequentiel", name="fusion-seq")
     */
    public function sequentiel(FusionService $fusionService)
    {
        $fusion = $fusionService->sequentiel();
        $notAccepted = $fusion[1];
        $fusion = $fusion[0];
        $handle = fopen($fusion, 'r');
        $f = 0;
        $g = 0;
        $total = 0;
        if ($handle){
            $ligne = fgetcsv($handle, 1000, ",");
            $ligne = fgetcsv($handle, 1000, ",");
            while ($ligne) {
                if (in_array("France", $ligne)){
                    $f++;
                } else{
                    $g++;
                }
                $ligne = fgetcsv($handle, 1000, ",");
            }
        }
        fclose($handle);
        $total = $f + $g;
        return $this->render('home/downloadSequentiel.html.twig', array('f' => $f, 'g' => $g, 'total' => $total, 'notAccepted' => $notAccepted));
        return $this->file($fusion);
    }

    /**
     * @Route("/fentrelace", name="fusion-entre")
     */
    public function entrelace(FusionService $fusionService)
    {
        $fusion = $fusionService->entrelace();
        $notAccepted = $fusion[1];
        $fusion = $fusion[0];
        $handle = fopen($fusion, 'r');
        $f = 0;
        $g = 0;
        $total = 0;
        if ($handle){
            $ligne = fgetcsv($handle, 1000, ",");
            $ligne = fgetcsv($handle, 1000, ",");
            while ($ligne) {
                if (in_array("France", $ligne)){
                    $f++;
                } else{
                    $g++;
                }
                $ligne = fgetcsv($handle, 1000, ",");
            }
        }
        fclose($handle);
        $total = $f + $g;
        return $this->render('home/downloadEntrelace.html.twig', array('f' => $f, 'g' => $g, 'total' => $total, 'notAccepted' => $notAccepted));
    }

    /**
     * @Route("/downloadSequentiel", name="download-seq")
     */
    public function downloadSequentiel(FusionService $fusionService){
        $fusion = $fusionService->sequentiel();
        $fusion = $fusion[0];

        return $this->file($fusion);
    }

    /**
     * @Route("/downloadEntrelace", name="download-entre")
     */
    public function downloadEntrelace(FusionService $fusionService){
        $fusion = $fusionService->entrelace();
        $fusion = $fusion[0];

        return $this->file($fusion);
    }


}