<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FusionService
{
    /**
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function sequentiel()
    {
        $handle1 = fopen("../var/uploads/small-french-data.csv", "r");
        $handle2 = fopen("../var/uploads/small-german-data.csv", "r");
        $fusion = "../var/uploads/french-german-client".date("m.d.Y").".csv";
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
        $liste = $this->selection($liste);
        $notAccepted = $liste[1];
        $liste = $liste[0];
        $liste = $this->projection($liste);
        foreach ($liste as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        $return = [];
        $return[] = $fusion;
        $return[] = $notAccepted;
        return $return;
    }

    /**
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     */
    public function entrelace()
    {
        $handle1 = fopen("../var/uploads/french-data.csv", "r");
        $handle2 = fopen("../var/uploads/german-data.csv", "r");
        $fusion = "../var/uploads/french-german-client".date("m.d.Y").".csv";
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
            echo "Ouverture fichier 1 impossible !";
        }
        $liste = $this->selection($liste);
        $notAccepted = $liste[1];
        $liste = $liste[0];
        $liste = $this->projection($liste);
        foreach ($liste as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        $return = [];
        $return[] = $fusion;
        $return[] = $notAccepted;
        return $return;
    }


    public function selection($liste)
    {
        $return = [];
        $compteur=0;
        $notAccepted = 0;
        $valid = false;
        foreach ($liste as $ligne) {
            if($ligne[0]!=='Number') {
                #comparer les tailles
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
                                    $notAccepted++;
                                    break;
                                }
                            } else {
                                $valid = true;
                            }
                        }

                    } else{
                        $notAccepted++;
                        $valid = false;
                    }
                }else{
                    $valid = false;
                    $notAccepted++;
                }

                if ($valid == false) {
                    unset($liste[$compteur]);
                }

            }
            $compteur++;
        }
        $return[] = $liste;
        $return[]= $notAccepted;
        return $return;
    }

    public function projection($liste)
    {
        $liste2 = array();
        $save = array('Number', 'Title', 'GivenName', 'Surname', 'EmailAddress', 'Birthday', 'TelephoneNumber', 'CCType', 'CCNumber', 'CVV2', 'CCExpires', 'StreetAddress', 'City', 'StateFull', 'ZipCode', 'CountryFull', 'Centimeters', 'Kilograms', 'Vehicle', 'Latitude', 'Longitude');
        $row = 0;
        $garde = array();
        $ligne1 = $liste[0];
        foreach ($ligne1 as $field) {
            if (in_array($field, $save)){
                $garde[] = $row;
            }
            $row++;
        }
        foreach ($liste as $ligne){
            $row = 0;
            foreach ($ligne as $test) {
                if (in_array($row, $garde)) {
                    $liste2[] = $test;
                }
                $row++;
            }
            $listefinal[] = $liste2;
            $liste2 = array();
        }
        return $listefinal;
    }
}