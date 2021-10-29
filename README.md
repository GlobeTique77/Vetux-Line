# Vetux-Line
**BERTRAND Julien**  
**DEMAZEUX Gabriel**  
**GONCALVES Maxence**  
**Groupe: Le nain et l'échelle**

[Ennoncé de la mission](https://ocapuozzo.github.io/mission-etl-csv/)  
[Notre projet hébergé](https://github.com/GlobeTique77/Vetux-Line)

29/10/2021

## Projet

Ce projet a pour but de créer une application web avec le framework symfony permettant à un gestionnaire de l'entreprise Vetux-Line de fusionner les fichiers csv contenant
les données des clients français et allemands tout en retirant selon certains critères des clients et des données inutiles pour ensuite les mettre dans une base de données.

## Première partie: Fusion++

### A/

Ce README.md que vous êtes entrain de lire.

### B/

Nous avions commencé par l'upload, c'est pourquoi nous avons fait notre injection de service dans UploadController.php (on parlera de ce controller dans la partie C).
Notre service s'appelle FusionService.php et se trouve dans src/Service.
Dans ce service nous avons mis la fusion séquentiel, la fusion entrelacé ainsi que les fonction selection et projection.

#### La fusion séquentiel

Voici le code de cette fusion:

```php
public function sequentiel()
    {
        $handle1 = fopen("../var/uploads/french-data.csv", "r");
        $handle2 = fopen("../var/uploads/german-data.csv", "r");
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
 ```
    
 Donc pour commencer, nous ouvrons les deux fichiers uploadés avec le fopen et comme paramètre 'r' pour read qu'on met dans les variables handle1 et 2.  
 Dans $fusion on met le chemin et le nom du fichier qui sera créer après la fusion et qui contiendra le résultat de cette fusion (on lui met la date du jour dans son nom).  
 Et avec $fp on lui donne la fonction fopen avec comme paramètre $fusion et 'wb', wb permet de créer le fichier s'il n'existe pas.  
 On crée ensuite un tableau vide où on mettre les lignes récupérés des deux premiers fichiers.  
 Avec le premier if, on vérifie que le fichier est bien ouvert et si oui on met dans $ligne1 la première ligne du premier fichier avec fgetcsv() 
 ce qui donne le nom des colonnes.  
 Après on vérifie l'ouverture du second fichier et on fait deux fois fgetcsv du fichier 2 (handle2) dans $ligne2 pour passer la première ligne avec le nom des colonnes.  
 Puis tant qu'il y a $ligne1 qui n'est pas false, on inscit la ligne dans l$liste[] puis ont refait un fgetcsv dans $ligne1 pour passer à la prochaine ligne et donc 
 mettre toutes les lignes du premier fichier dans $liste[].  
 On fait pareil pour $ligne2 qui lui commence directement avec le premier client allemand et qui les met un par un dans $liste[].  
 Ensuite on ferme les fichiers avec fclose.  
 On passe $liste dans la fonction selection et nous la renvoie avec une variable en plus: $notAccepted (le nombre de clients retirés de la liste par certain critère).  
 On remet la liste des clients acceptés dans $liste et on la passe dans la foncion projection qui supprime les colonnes qui ne sont pas voulues par l'entreprise.  
 Puis avec le foreach, on passe chaque ligne de la liste dans le fichier résultat avec fputcsv($fp, $fields).  
 Après on ferme le fichier résultat et on crée un tableau $return pour retourner le chemin du fichier résultat et le nombre de clients non acceptés.  
 
 #### La fusion entrelacé
 
 ```php
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
 ```
 C'est preque la même chose que l'autre fusion mais cette fois on utilise qu'un seul while qui vérifie si $ligne1 ou $ligne2 n'est pas false pour éviter le problème de si
 on est arrivé à la fin d'un fichier et pas de l'autre, qu'on puisse quand même continuer.  
 Et après on met les deux if comme la fusion séquentiel dans ce while.  
 Ce qui permet de mettre dans $liste un coup le contenu de $ligne1 et après celui de $ligne2 et ainsi de suite.
 
 #### La séléction 
 
 Voici le code:
 
 ```php
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
 ```
 
 Cette fonction prend en paramètre $liste qu'est la liste que lui envoie une des deux fusions.  
 On crée des variables et un tableau qui serviront plus tard.  
 ![mickey](https://c.tenor.com/iSvgDMm3SMQAAAAC/la-maison-de-mickey.gif)  
 Avec le foreach, on fera le test sur chaque ligne sauf pour la première qui contient les colonnes grâce à if($ligne[0]!=='Number') qui vérifie que le premier champ de 
 la ligne ne soit pas 'Number'.  
 Le premier test est celui des tailles en cm et pieds/pouces.  
 On prend les valeurs mais c'est pas comparable comme les pieds/pouces n'ont pas la même syntax.  
 Alors on converti la taille en cm en pieds/pouces pour ensuite les comparer.
 Si c'est les même on passe au second test, sinon on met +1 à $notAccepted (le compteur de clients rejetés), 
 on remet $valid = false car après les trois premiers, ceux qui passait pas ce test avait $valid qui était true donc on a dû forcé avec $valid = false.  
 Puis on supprime la ligne de $liste avec :
 
 ```php
 if ($valid == false) {
    unset($liste[$compteur]);
    }
 ```
 
 Passons au deuxième test, la vérification de la majorité.  
 Tout d'abord on récupère la date de la ligne testé, puis on vérifie la validité de la date, si c'est pas bon, on lui donne comme age 0.  
 Après on calcule son age en fonction de la date du jour et de sa date de naissance et enfin on vérifie qu'il a 18 ans ou plus.  
 S'il passe pas, il se fait supprimé et $notAccepted augmente de la même manière que le premier test.  
 
 Pour le dernier test, celui des numéros de cartes de crédit, on doit vérifier s'il n'est pas en double ou plus.  
 On récupère le CCN de la ligne puis on fait un autre foreach de $liste.  
 On vérifie si $ligne2 n'est pas la line avec le nom des colonnes ainsi que $ligne2 n'a pas le même numéro que $ligne1 ni le même nom pour éviter 
 qu'il se compare à lui même.  
 Puis on vérifie que les CCN sont différents, si oui $valid = true donc il ne se fait pas supprimé, sinon $valid = false et on sort du foreach pour supprimé la ligne 
 et augmenté $notAcccepted .
 
 Après cette série de test que passe une ligne, comme dit plus haut, en fonction de $valid, il se fait supprimé de $liste ou non et $notAccepted augmente ou pas.
 Puis $compteur augmente, c'est cette vraiable qui permet de donner un numéro à la ligne qui se fera supprimer de $liste par unset($liste[$compteur]).
 Ensuite on met dans le tableau $return la liste triée ($liste) et $notAccepted.  
 Et enfin on return $return.
 
 #### La projection
 
 Voici le code: 
 
 ```php
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
            foreach ($ligne as $field) {
                if (in_array($row, $garde)) {
                    $liste2[] = $field;
                }
                $row++;
            }
            $listeFinal[] = $liste2;
            $liste2 = array();
        }
        return $listeFinal;
    }
 ```
 
 Cette fonction permet de garder les colonnes qu'on veut et qui sont dans le tableau $save.  
 Elle prend elle aussi en paramètre $liste donné par les fonctions fusion.  
 On récupère la première ligne de la liste donc celle qui contient les colonnes.
 Avec un foreach on va regarder chaque colonne pour vérifier avec if(in_array($field, $save)) si la colonne est bien dans celle que l'on veut garder.  
 Si oui on met $row (l'index des colonnes) dans le tableau $garde et après le if on augmente $row de un et ainsi de suite.  
 Après avec un foreach on passe chaque ligne et on remet $row à 0 puis on refait un autre foreach pour passer chaque colonne.  
 On vérifie si dans le $garde il y a bien le bon index avec $row, si oui on passe $field (donc ce que contient la colonne) dans $liste2 qui est un tableau 
 représentant la ligne triée.  
 On sort du if et on augmente de un $row et on recommence jusq'à sortir du foreach pour ensuite mettre $liste2 dans $listeFinal et rénitialiser $liste2.  
 Enfin, après avoir trié chaque ligne et l'avoir mis dans $listeFinal, on return $listeFinal.
 
 Ces quatre fonctions sont dans le service FusionService.php.  
 On les utilise dans UploadController.php grâce à use App\Service\FusionService; au début du controlleur.  
 Puis dans les fonctions en mettant dans les paramètres FusionService $fusionService et directement à l'intérieur des fonctions avec 
 $variable = $fusionService->nomDeLaFonctionAUtiliser(); (ici soit sequeniel() soit entrelace() ).  
 
 ### C/
 
 Comme on avait besoin de différents types d'utilisateurs, nous avions besoin du security-bundle.  
 Pour l'installer on a fait la commande:  
 composer require symfony/security-bundle 
 Et comme on a besoin d'une DB on a modifié dans le fichier .env la ligne commençant par DATABASE_URL="mysql... par  
 DATABASE_URL="mysql://sio:sio@127.0.0.1:3306/vetux-line?serverVersion=mariadb-10.4.21" (la version est différente entre les membres du groupe).  
 Puis une classe Utilisateur.php avec:  
 php bin/console make:user  
 Et on choisit comme propriété unique username.  
 
 
 
 
