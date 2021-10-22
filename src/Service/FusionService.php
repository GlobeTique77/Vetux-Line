<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FusionService
{
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
        return $this->file($fusion);

        //dump($liste);
        //exit;
    }


    public function selection(Request $request, string $uploadDir,
                              FileUploader $uploader, LoggerInterface $logger): Response
    {
        $handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        $ligne = fgetcsv($handle1, 1000, ",");
        $ligne2 = fgetcsv($handle1, 1000, ",");
        $ligne2 = fgetcsv($handle1, 1000, ",");
        $liste = array();
        $liste2 = array();

        #comparer les tailles
        $inchSize = $ligne2[39];
        $cmSize = $ligne2[40];
        $inches = $cmSize/2.54;
        $feet = intval($inches/12);
        $inches = $inches%12;
        $inchSize2 = sprintf("%d' %d".'"', $feet, $inches);
        if ($inchSize===$inchSize2){
            #vérifier être majeur
            $birthday = $ligne2[21];
            $date = explode("/", $birthday);
            if(count($date)<=2){
                $age=0;
            }
            $dateBonFormat = $date[2]."-".$date[1]."-".$date[0];
            $date = explode("-", $dateBonFormat);
            $age = date('Y') - $date[0];
            if (date('m') < $date[2]) {
                $age--;
            }
            elseif(date('d') < $date[1]){
                $age--;
            }
            if($age>=18){
                #vérif carte crédit
                $CCN = $ligne2[24];
                $ligne3 = fgetcsv($handle1, 1000, ",");
                $valid = true;
                while($ligne3) {
                    if ($CCN !== $ligne3[24]) {
                        $valid = true;
                        dump($ligne3[24]);
                        $ligne3 = fgetcsv($handle1, 1000, ",");
                    } else {
                        $valid = false;
                        break;
                    }
                }
                if($valid === true){
                    echo "c'est good mother fucker";
                    //return true;
                }

            }


        }


        foreach($ligne as $champs){
            $liste[]=$champs;
        }
        foreach($ligne2 as $champs){
            $liste2[] = $champs;
        }
        dump($liste, $liste2, $ligne[1], $inchSize, $inchSize2, $cmSize, $age, $birthday, $CCN, $valid);
        exit;
    }
}