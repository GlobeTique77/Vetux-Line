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
     * @ORM\OneToMany(targetEntity="App\Entity\Client", mappedBy="vehicule2")
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
