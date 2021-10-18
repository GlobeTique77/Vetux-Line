<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;

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
            return new Response("No file specified",
                Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }
        foreach ($files as $file)
        {
            $filetype = $file->getMimeType();
            dump($filetype);

            $filename = $file->getClientOriginalName();
            $uploader->upload($uploadDir, $file, $filename);
        }
        exit;
        return new Response("File uploaded",  Response::HTTP_OK,
            ['content-type' => 'text/plain']);
    }

    /**
     * @Route("/test1", name="test-fusion-seq")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function sequentiel(Request $request, string $uploadDir,
                          FileUploader $uploader, LoggerInterface $logger): Response
    {
        $handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        $handle2 = fopen("../var/uploads/small-german-data.csv", "r");
        $fusion = "../var/uploads/test1.csv";
        $fp = fopen($fusion, 'wb');
        $liste = array();
        if($handle1){
            $ligne1 = fgetcsv($handle1, 1000, ",");
            if($handle2) {
                $ligne2 = fgetcsv($handle2, 1000, ",");  //skip 1ere ligne
                $ligne2 = fgetcsv($handle2, 1000, ",");
                while ($ligne1) {
                    $liste[] = $ligne1;
                    $ligne1 = fgetcsv($handle1, 1000, ",");
                }

                while ($ligne2){
                    $liste[] = $ligne2;
                    $ligne2 = fgetcsv($handle2, 1000, ",");
                }
                fclose($handle1);
                fclose($handle2);
            }else{
                echo "Ouverture fichier 2 impossible !";
            }
        }else{
            echo "Ouverture fichier 1 impossible !";
        }
        foreach ($liste as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        dump($liste);
        exit;
    }

    /**
     * @Route("/test2", name="test-fusion-entre")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function entrelace(Request $request, string $uploadDir,
                               FileUploader $uploader, LoggerInterface $logger): Response
    {
        $handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        $handle2 = fopen("../var/uploads/small-german-data.csv", "r");
        $fusion = "../var/uploads/test2.csv";
        $fp = fopen($fusion, 'wb');
        $liste = array();
        if($handle1){
            $ligne1 = fgetcsv($handle1, 1000, ",");
            if($handle2) {
                $ligne2 = fgetcsv($handle2, 1000, ","); //skip 1ere ligne
                $ligne2 = fgetcsv($handle2, 1000, ",");
                while ($ligne1 || $ligne2) {
                    if ($ligne1) {
                        $liste[] = $ligne1;
                        $ligne1 = fgetcsv($handle1, 1000, ",");
                    }
                    if ($ligne2) {
                        $liste[] = $ligne2;
                        $ligne2 = fgetcsv($handle2, 1000, ",");
                    }
                }
                fclose($handle1);
                fclose($handle2);
            }else{
                echo "Ouverture fichier 2 impossible !";            }
        }else{
            echo "Ouverture fichier 1 impossible !";        }
        foreach ($liste as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        dump($liste);
        exit;
    }
}