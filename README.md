# Vetux-Line
**BERTRAND Julien**  
**DEMAZEUX Gabriel**  
**GONCALVES Maxence**  
**Groupe: Le nain et l'échelle**

[Enoncé de la mission](https://ocapuozzo.github.io/mission-etl-csv/)  
[Notre projet hébergé](https://github.com/GlobeTique77/Vetux-Line)

30/10/2021

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
 On a concrétement suivi le td secufony de Mr.Chamillard.  
 [Le td en question](https://slam-vinci-melun.github.io/sio22/phase1/TD_Secufony_2.odt)
 
 On a créé 3 types d'utilisateur:  
 - ROLE_USER (donné à chaque utilisateur)
 - ROLE_ADMIN (peut voir la liste des utilisateurs, les modifier et supprimer)
 - ROLE_GESTIONNAIRE (peut faire la fusion des fichiers csv)
 
 Le code du template pour chaque page: 
 
 ```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Vetux-Line{% endblock %}</title>
    {% block stylesheets %}
        <link rel="stylesheet" href="https://bootswatch.com/4/yeti/bootstrap.min.css">
    {% endblock %}
</head>
<body>
<nav class="navbar navbar-light bg-light">
    <a class="navbar-brand">Navbar</a>
    {% if app.user %}
        <di>
            <a class="btn btn-sm btn-primary" href="{{ path('user_index') }}">Accueil</a>
        </di>
        {% set admin = 'ROLE_ADMIN' %}
        {% if admin in app.user.roles %}
            <div>
                <a class="btn btn-sm btn-success" href="{{ path('admin_index') }}">Espace administrateur</a>
            </div>
        {% endif %}
        {% set gestionnaire = 'ROLE_GESTIONNAIRE' %}
        {% if gestionnaire in app.user.roles %}
            <div>
                <a class="btn btn-sm btn-success" href="{{ path('home') }}">Espace gestionnaire</a>
            </div>
        {% endif %}
        <div>
            Bonjour {{ app.user.username }} <a class="btn btn-sm btn-danger" href="{{ path('app_logout') }}">Déconnexion</a>
        </div>
    {% else %}
        <div>
            <a class="btn btn-sm btn-primary" href="{{ path('utilisateur_new') }}">S'inscrire</a>
            <a class="btn btn-sm btn-success" href="{{ path('app_login') }}">Se connecter</a>
        </div>
    {% endif %}
</nav>
<div class="container">
    {% if message is defined %}
        <div class="alert alert-danger">
            {{ message }}
        </div>
    {% endif %}

    {% block body %}
    {% endblock %}

</div>
{% block javascripts %}
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous">
    </script>
    <script src="/js/script.js"></script>
{% endblock %}
</body>
</html>
```

Dans la navbar on vérifie si l'utilisateur est connécté avec {% if app.user %} et pour connaitre son rôle c'est dans app.user.roles.  
On crée des variable contenant les noms des rôles et on demande si le contenue de cette variable est dans app.user.roles pour vérifier le rôle.  
Dans le cas du gestionnaire:  
{% set gestionnaire = 'ROLE_GESTIONNAIRE' %}  
{% if gestionnaire in app.user.roles %}  
Et après on met le chemin d'accès pour faire l'uploads des fichiers puis la fusion:  
```html
<a class="btn btn-sm btn-success" href="{{ path('home') }}">Espace gestionnaire</a>
```

Ce qui donne pour le gestionnaire:  
![image](https://user-images.githubusercontent.com/78152264/139484550-2f1d812e-0e91-437e-877d-1318a1c0577c.png)

Et quand on clique sur son espace, on arrive sur le chemin /home et côté client donne:  
![image](https://user-images.githubusercontent.com/78152264/139485618-ddf29c2e-917a-4849-aad3-cdb5afa8be7d.png)

Voici le code du formulaire:  
```html
<form action="{{ path('do-upload') }}" method="post" enctype="multipart/form-data">

        <input type="hidden" name="token" value="{{ csrf_token('upload') }}" />

        <div>
            <label for="myfile">File to upload:</label>
            <input type="file" name="myfile[]" id="myfile1" accept=".csv">
        </div>
        <div>
            <label for="myfile">File to upload:</label>
            <input type="file" name="myfile[]" id="myfile2" accept=".csv">
        </div>

        <button type="submit">Send</button>

    </form>
```

Comme on le voit à la prmeière ligne, ça envoie à la route do-upload qui se trouve dans le UploadController.php.  
Donc la fonction index() qui prend comme service le FileUploader.php .  
On a pris le code de cette exemple d'upload sur symfony donné par l'énnoncé de la mission.  
[L'exemple](https://zetcode.com/symfony/uploadfile/)  

On a modifié cette partie là:  

```php
$files = $request->files->get('myfile');
if (empty($files))
        {

            $this->addFlash('notice', 'No file specified');
            return $this->render('home/index.html.twig');
        }
        foreach ($files as $file)
        {
            $filetype = $file->getMimeType();

            $filename = $file->getClientOriginalName();
            $uploader->upload($uploadDir, $file, $filename);
        }
        $this->addFlash('notice', 'Files uploaded');
        return $this->render('home/fusion.html.twig');
```

La première ligne récupère dans le tableau $files les fichier envoyés par post du formulaire.  
On vérifie s'il y a des fichiers, sinon on renvoie au formulaire avec un message flash qui précise que le gestionnaire n'a envoyé aucun fichier.  
Avec le foreach on regarde pour chaque fichier:  
- son type
- son nom  

Puis on utilise la fonction upload du service FileUploader.php pour finaliser l'upload et enregistrer les fichiers sur le serveur.  
Le return envoie le client sur la page home/fusion.html.twig.  
Cette page contient 2 liens, un pour la fusion séquentiel, l'autre pour la fusion entrelacé.  
![image](https://cdn.discordapp.com/attachments/775368238137606184/903723875811065986/Capture.PNG)  

Le code: 
```html
{% extends 'userbase.html.twig' %}

{% block title %}Make fusion{% endblock %}

{% block body %}

    <a href="{{ path('fusion-seq', relative = false) }}">Faire la fusion séquentiel</a>
    <br><br>
    <a href="{{ path('fusion-entre', relative = false) }}">Faire la fusion entrelacé</a>

{% endblock %}
```

Les deux liens nous amènent sur deux focntions de UploadController.php, la fonction sequentiel et la fonction entrelace.  

### D/

Les fonctions sequentiel et entrelace utilisent le FusionService et sont presque des fonctions clonés.  
Voici le code de sequantiel(): 

```php
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
    }
```

Ce qui change pour entrelace() c'est sa route:  
@Route("/fentrelace", name="fusion-entre")  
L'appel à la fonction qui fait la fusion des csv:  
$fusion = $fusionService->entrelace();  
Et la page où est renvoyé les donnés:  
return $this->render('home/downloadEntrelace.html.twig', array('f' => $f, 'g' => $g, 'total' => $total, 'notAccepted' => $notAccepted));

Les méthodes de fusion retournent deux choses:  
-le fichier résultat de la fusion  
-le nombre de clients rejetés  
On les récupère donc avec la première ligne et on les met dans 2 variables différentes.  
Ensuite on ouvre le fichier de résultat de la fusion avec le fopen et on crée trois nouvelles variables qui représentent le nombre de clients français,  
le nombre de clients allemands et le nombre total de client (accepté).  
Après on vérifie si le fichier est bien ouvert puis on passe directement à la deuxième ligne en faisant deux fois fgetcsv.  
Tant qu'il y a une ligne, on regarde si dans cette ligne se trouve 'France' avec if (in_array("France", $ligne)).  
Si ça renvoie true, on augmente $f de un, si ça renvoie false, ça augmente $g de un.  
Puis on passe à la ligne d'après et ainsi de suite.  
A la fin du while, on ferme le fichier et on calcul le total de client avec $total = $f + $g.  
Et enfin on retourne à la page home/downloadSequentiel.html.twig ou home/downloadEntrelace.html.twig les différentes variables qui sont là pour des statistiques.   

Voilà ce que donne cette page (même visuel et même statistiques pour les deux fusions):  
![image](https://cdn.discordapp.com/attachments/775368238137606184/903732385194065990/Capture.PNG)  

Et le code:  
```html
{% extends 'userbase.html.twig' %}

{% block title %}Download file{% endblock %}

{% block body %}

    <a href="{{ path('download-seq', relative = false) }}">Télécharger la fusion séquentiel</a>

    {% if f is defined %}
        <p>nombre de français: {{ f }}</p>
    {% endif %}
    {% if g is defined %}
        <p>nombre d'allemands: {{ g }}</p>
    {% endif %}
    {% if total is defined %}
        <p>nombre de clients acceptés: {{ total }}</p>
    {% endif %}
    {% if notAccepted is defined %}
        <p>nombre de clients rejetés: {{ notAccepted }}</p>
    {% endif %}

{% endblock %}
```

Le href renvoie à la fonction de la route download-seq pour la fusion séquentiel ou download-entre si on a choisit la fusion entrelacé avant.  
Les {% if variable is defined %} vérifient si les variables existent donc si la page les a bien reçu.  
Et pour utiliser ces variables on fait {{ variable }}.  
Les routes cités un peu plus haut renvoient aux fonctions downloadSequentiel et downloadEntrelace qui elles aussi font appel au service FusionService.php  
Voici le code de downloadSequentiel:  
```php
    /**
     * @Route("/downloadSequentiel", name="download-seq")
     */
    public function downloadSequentiel(FusionService $fusionService){
        $fusion = $fusionService->sequentiel();
        $fusion = $fusion[0];

        return $this->file($fusion);
    }
```

Les changements pour downloadEntrelace sont la route:  
@Route("/downloadEntrelace", name="download-entre")  
et la fonction appellée:  
$fusion = $fusionService->entrelace();

Nous avons dis plus haut que sequentiel() et entrelace() retournaient un tableau contenant le fichier et le nombre de clients rejetés.  
C'est pour ça qu'on met dans $fusion seulement le premier élément de $fusion[] donc le fichier résultat de la fusion.  
Le return renvoie à l'utilisateur le fichier directement en téléchargement donc le download.  
 
 ## Deuxième partie: ETL
 
### A/

Nous avons créé 3 entités selon le shéma conceptuel client-auto que veut Vetux-Line.  
On a donc l'entité Client, l'entité Vehicule et l'entité Marque.  
Pour les créer, on a utilisé la commande: php bin/console make:entity nameEntity  
On a dû renseigner les champs juste après avoir entré la commande.  
Les champs pour Client sont les champs gardés avec la projection.  
Voici le code de cette entité:  
```php
<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 */
class Client
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $genre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $titre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $prenom;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="date")
     */
    private $date_naissance;

    /**
     * @ORM\Column(type="integer")
     */
    private $num_tel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $CCType;

    /**
     * @ORM\Column(type="integer")
     */
    private $CCNumber;

    /**
     * @ORM\Column(type="integer")
     */
    private $CVV2;

    /**
     * @ORM\Column(type="date")
     */
    private $CCExpires;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Adresse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ville;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $region;

    /**
     * @ORM\Column(type="integer")
     */
    private $code_zip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pays;

    /**
     * @ORM\Column(type="integer")
     */
    private $taille;

    /**
     * @ORM\Column(type="float")
     */
    private $poids;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicule", inversedBy="clients")
     * @ORM\JoinColumn(nullable=true)
     */
    private $vehicule;

    /**
     * @ORM\Column(type="float")
     */
    private $GPS_latitude;

    /**
     * @ORM\Column(type="float")
     */
    private $GPS_longitude;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTimeInterface $date_naissance): self
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getNumTel(): ?int
    {
        return $this->num_tel;
    }

    public function setNumTel(int $num_tel): self
    {
        $this->num_tel = $num_tel;

        return $this;
    }

    public function getCCType(): ?string
    {
        return $this->CCType;
    }

    public function setCCType(string $CCType): self
    {
        $this->CCType = $CCType;

        return $this;
    }

    public function getCCNumber(): ?int
    {
        return $this->CCNumber;
    }

    public function setCCNumber(int $CCNumber): self
    {
        $this->CCNumber = $CCNumber;

        return $this;
    }

    public function getCVV2(): ?int
    {
        return $this->CVV2;
    }

    public function setCVV2(int $CVV2): self
    {
        $this->CVV2 = $CVV2;

        return $this;
    }

    public function getCCExpires(): ?\DateTimeInterface
    {
        return $this->CCExpires;
    }

    public function setCCExpires(\DateTimeInterface $CCExpires): self
    {
        $this->CCExpires = $CCExpires;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->Adresse;
    }

    public function setAdresse(string $Adresse): self
    {
        $this->Adresse = $Adresse;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getCodeZip(): ?int
    {
        return $this->code_zip;
    }

    public function setCodeZip(int $code_zip): self
    {
        $this->code_zip = $code_zip;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(float $poids): self
    {
        $this->poids = $poids;

        return $this;
    }

    public function getVehicule(): Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(Vehicule $vehicule)
    {
        $this->vehicule = $vehicule;
    }

    public function getGPSLatitude(): ?float
    {
        return $this->GPS_latitude;
    }

    public function setGPSLatitude(float $GPS_latitude): self
    {
        $this->GPS_latitude = $GPS_latitude;

        return $this;
    }

    public function getGPSLongitude(): ?float
    {
        return $this->GPS_longitude;
    }

    public function setGPSLongitude(float $GPS_longitude): self
    {
        $this->GPS_longitude = $GPS_longitude;

        return $this;
    }
}
```

Pour les entités Vehicule et Marque, on a les champs du shéma coonceptuel.  
Le code de Vehicule:  
```php
<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=VehiculeRepository::class)
 */
class Vehicule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $modele;

    /**
     * @ORM\Column(type="integer")
     */
    private $annee;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Client", mappedBy="vehicule")
     */
    private $clients;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Marque", inversedBy="vehicules")
     * @ORM\JoinColumn(nullable=true)
     */
    private $marque;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): self
    {
        $this->modele = $modele;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): self
    {
        $this->annee = $annee;

        return $this;
    }

    public function __construct()
    {
        $this->clients = new ArrayCollection();
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients()
    {
        return $this->clients;
    }

    public function getMarque(): Marque
    {
        return $this->marque;
    }

    public function setMarque(Marque $marque)
    {
        $this->marque = $marque;
    }
}

```
Et le code de Marque:  
```php
<?php

namespace App\Entity;

use App\Repository\MarqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=MarqueRepository::class)
 */
class Marque
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vehicule", mappedBy="marque")
     */
    private $vehicules;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
    }

    /**
     * @return Collection|Vehicule[]
     */
    public function getVehicules()
    {
        return $this->vehicules;
    }
}
```

On voit bien dans le shéma conceptuel que ces entités sont liés.  
Pour cela on utilise many to one et one to many.  
Il y a une relation entre Client et Vehicule et une autre pour Vehicule et Marque.  
Un client peut avoir au maximum un véhicule et plusieurs clients peuvent avoir le même modèle de véhicule.  
C'est pourquoi nous aurons dans Client.php un many to one et dans Vehicule.php un one to many.  

Client.php
```php
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicule", inversedBy="clients")
     * @ORM\JoinColumn(nullable=true)
     */
    private $vehicule;
    [...]
    public function getVehicule(): Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(Vehicule $vehicule)
    {
        $this->vehicule = $vehicule;
    }
```
Vehicule.php
```php
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Client", mappedBy="vehicule")
     */
    private $clients;
    [...]
    /**
     * @return Collection|Client[]
     */
    public function getClients()
    {
        return $this->clients;
    }
```
Pour la relation entre Vehicule et Marque, Vehicule aura un many to one et Marque un one to many.  
Un véhicule n'a qu'une seule marque mais plusieurs véhicules peuvent avoir la même marque.  

Vehicule.php
```php
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Marque", inversedBy="vehicules")
     * @ORM\JoinColumn(nullable=true)
     */
    private $marque;
    [...]
    public function getMarque(): Marque
    {
        return $this->marque;
    }

    public function setMarque(Marque $marque)
    {
        $this->marque = $marque;
    }
```

Marque.php
```php
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vehicule", mappedBy="marque")
     */
    private $vehicules;
    [...]
    /**
     * @return Collection|Vehicule[]
     */
    public function getVehicules()
    {
        return $this->vehicules;
    }
```

Nos entités sont bien reliés.  

### B/

Passons maintenant au lien avec le serveur de base de données, ici MySql.  
Dans le fichier .env on doit modifier cette ligne commençant par DATABASE_URL="mysql .  
Voici un exemple des changements comme nous n'avons pas les mêmes versions de serveur de base de données ou de comptes utilisateurs au sein du groupe.  
DATABASE_URL="mysql://sio:sio@127.0.0.1:3306/vetux-line?serverVersion=mariadb-10.4.21"  
Ici on a remplacé db_user par sio, db_password par sio, db_name par vetux-line et ce qui suit après le = par mariadb-10.4.21.  
Ensuite on éxécute ces deux commandes pour faire la migration de nos entités vers la DB avec doctrine:  
php bin/console make:migration  
php bin/console doctrine:migrations:migrate  
Et ça donne dans notre base de donnée vetux-line les tableaux suivants:  
![image](https://user-images.githubusercontent.com/78152264/139537828-f2321458-28b0-4e99-90b2-19f040d811ce.png)  
(Le tableau utilisateur sert pour la gestion des différent type d'utilisateur pour la première partie et doctrine migration version est un tableau montrant 
toutes les migrations.)  

## Evil User Stories

### A/

En tant que personne malveillante, j'ai réussi à savoir que cette application est faite avec symfony et que les développeurs ont utilisés le security bundle,  
ils ont certainement hashé les mots de passe à la création mais pas à la modification alors je veux avoir accès à la base de données pour récupérer les mots de passe et  
les identifiants des utilisateurs qui ont changé leur mot de passe.

En tant que développeur, je veux hasher les mots de passe à leur modification pour empêcher un utilisateur malveillant de pouvoir les lire en clair sur  
la base de données et les utilisés pour une utilisation malveillante. Pour cela dans UtilisateurController.php je dois importer la librairie UserPasswordHasherInterface  
avec: 

```php
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
```

Puis dans le même contrôleur, je modifie la fonction edit:

```php
 #[Route('/admin/{id}/edit', name: 'utilisateur_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur->setPassword(
                $passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }
```
Dans les paramètres de la fonction, on instancie un objet de la classe UserPasswordHasherInterface puis on fait appel à la fonction hashPassword de la même classe  
dans la fonction setPassword de la classe Utilisateur, ainsi, ça hashera le mot de passe tout juste modifié.


### B/

En tant que personne malveillante, je veux avoir avoir accès à la base de données pour récupérer des informartions sur l'application Vetux-Line mais  
aussi d'autres bases de données qui sont sur le même serveur que Vetux-Line.

En tant que développeur, je vais créer sur le serveur de base de données  un utilisateur spécialement pour la base de données de Vetux-Line pour empêcher un utilisateur  
malveillant d'accéder à cette base de données ainsi qu'à d'autre sur le même serveur. Pour cela sur phpMyAdmin je crée ce nouvel utilisateur comme ceci:

![image](https://user-images.githubusercontent.com/78152264/140641626-46d75659-d71d-4a80-bedd-2287d71d665c.png)

Et dans le fichier .env on précise les informations de la base de données avec cette ligne:

```php
    DATABASE_URL="mysql://vetux_line:wNgEf23m*7MZdWZ9@127.0.0.1:3306/vetux_line?serverVersion=mariadb-10.4.21"
```

### C/

En tant que personne malveillante, je veux avoir accès à certaines pages sans authentification pour pouvoir avoir accès aux actions et aux données  
permises à certains rôles.

En tant que développeur, je veux autorisé seulement certains rôles à aller sur certaines pages pour éviter qu'un utilisateur malveillant ait accès aux actions et données  
de ces rôles. Pour cela je modifie dans le fichier security.yaml les lignes de acces_control:

```php
access_control:
         - { path: ^/utilisateur/admin, roles: ROLE_ADMIN }
         - { path: ^/home, roles: ROLE_GESTIONNAIRE }
```
Ainsi, toutes les routes commençant par /utilisateur/admin seront uniquemant accessibles par les utilisateur avec le rôle ROLE_ADMIN et pareil pour  
les routes commençant par /home pour les utilisateur avec le rôle ROLE_GESTIONNAIRE.  
Par exemple, UtilisateurController.php a juste après ses uses et avant sa classe, il y a le nom de sa route:

```php
#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
```
Et dans les routes des admins, il y a /admin donc on retrouve bien /utilisateur/admin (voir la fonction du A/ pour exemple).  
Pour /home, dans la UploadController.php là où il y a toute les fonctions des gestionnaires, on met:

```php
#[Route('/home')]
class UploadController extends AbstractController
```
On retouve le /home .

### D/

En tant que personne malveillante, je veux uploader des fichiers pour faire planter le serveur ou le hacker.

En tant que développeur, je veux seulement autoriser les fichiers csv dans l'upload pour éviter des attaques malveillantes. Pour cela je dois dans UploadController.php  
dans la fonction index, vérifié le type de chaque fichier reçu par la fonction: 

```php
foreach ($files as $file)
        {
            $filetype = $file->getMimeType();
            if (str_contains($filetype, '/csv')){
                $filename = $file->getClientOriginalName();
                $uploader->upload($uploadDir, $file, $filename);
            }
            else {
                return $this->render('home/index.html.twig');
            }
        }
```
getMimeType() renvoie le type de fichier avec son extension sous forme: "type/extension".  
Ici on vérifie juste si dans ce retour on a bien l'extension csv avec un str_contains et si oui on récupère le nom du fichier et on l'upload.  
Sinon on renvoie à la page d'upload.  
Nos fichiers csv ont comme MimeType application/csv et pas text/cvs, on a donc dû vérifier seulement l'extension.



## Conclusion

Nous passons directement à la conclusion car nous n'avons pas pu réaliser le reste (les Evil User Stories seront terminés pour la semaine prochaine).  
Dans la première partie il nous manque les tests unitaire et dans la seconde tout ce qui est à partir de la fonction ETL. 
Nous commencions à étudier comment faire la focntion ETL.  
Pourquoi n'avons nous pas finit le projet ?  
La principale raison est la mauvaise gestion du temps mais pas que, on a aussi eu des problèmes matérielle et des problèmes avec git qui nous on fait perdre beaucoup de temps.  
Ensuite pendant la première semaine des vacances, c'était compliqué de travailler à cause de plusieurs facteurs personels et qui sont pas forcément prévus.  
Ce qui nous a fait perdre encore plus de temps.  
Tout cela aurait pû être éviter si nous avions mieux géré notre temps au début et nous en sommes conscients.


