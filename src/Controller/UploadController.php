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

            $filename = $file->getClientOriginalName();
            $uploader->upload($uploadDir, $file, $filename);
        }
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
        $liste = $this->selection($liste);
        foreach ($liste as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        return $this->file($fusion);

        //dump($liste);
        //exit;
    }


    public function selection($liste)
    {
        //$handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        //$ligne = fgetcsv($handle1, 1000, ",");
        //$ligne2 = fgetcsv($handle1, 1000, ",");
        //$ligne2 = fgetcsv($handle1, 1000, ",");
        //$liste = array();
        //$liste2 = array();
        $compteur=0;
        $valid = false;
        #comparer les tailles
        foreach ($liste as $ligne) {
            if($ligne[0]!=='Number') {

                $inchSize = $ligne[39];
                $cmSize = $ligne[40];
                $inches = $cmSize / 2.54;
                $feet = intval($inches / 12);
                $inches = $inches % 12;
                $inchSize2 = sprintf("%d' %d" . '"', $feet, $inches);
                if ($inchSize === $inchSize2) {
                    #vérifier être majeur
                    $birthday = $ligne[21];
                    $date = explode("/", $birthday);
                    if (count($date) <= 2) {
                        $age = 0;
                    } else {
                        $dateBonFormat = $date[2] . "-" . $date[1] . "-" . $date[0];
                        $date = explode("-", $dateBonFormat);
                        $age = date('Y') - $date[0];
                        if (date('m') < $date[2]) {
                            $age--;
                        } elseif (date('d') < $date[1]) {
                            $age--;
                        }
                    }
                    if ($age >= 18) {
                        #vérif carte crédit
                        $CCN = $ligne[24];
                        $valid = true;
                        foreach ($liste as $ligne2) {
                            if ($ligne2[0] !== 'Number' && $ligne[0] !== $ligne2[0] && $ligne[2] !== $ligne2[2]) {
                                if ($CCN !== $ligne2[24]) {
                                    $valid = true;
                                } else {
                                    $valid = false;
                                    break;
                                }
                            } else {
                                $valid = true;
                            }
                        }

                    }


                }

                if ($valid === false) {
                    unset($liste[$compteur]);
                }

            }
            $compteur++;
        }
        return $liste;
    }
    /**
     * @Route("/test4", name="test-colone")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function projection(Request $request, string $uploadDir,
                              FileUploader $uploader, LoggerInterface $logger): Response
    {
        $handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        $fusion = "../var/uploads/test3.csv";
        $fp = fopen($fusion, 'wb');
        $liste = array();
        $save = array('Number', 'Title', 'GivenName', 'Surname', 'EmailAddress', 'Birthday', 'TelephoneNumber', 'CCType', 'CCNumber', 'CVV2', 'CCExpires', 'StreetAddress', 'City', 'StateFull', 'ZipCode', 'CountryFull', 'Centimeters', 'Kilograms', 'Vehicle', 'Latitude', 'Longitude');
        $raw = 0;
        $garde = array();
        if($handle1) {
            $ligne1 = fgetcsv($handle1, 1000, ",");
            foreach ($ligne1 as $item) {
                if (in_array($item, $save)){
                    $garde[] = $raw;
                }
                $raw++;
            }
        }
            while ($ligne1) {
                $raw = 0;
                foreach ($ligne1 as $test) {
                    if (in_array($raw, $garde)) {
                        $liste[] = $test;
                    }
                $raw++;
                }
                $listefinal[] = $liste;
                $liste = array();
            $ligne1 = fgetcsv($handle1, 1000, ",");
            }
            foreach ($listefinal as $field) {
                fputcsv($fp, $field);
            }


        dump($listefinal);
        exit;
    }

}