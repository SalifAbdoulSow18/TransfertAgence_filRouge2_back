<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CompteRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Asset;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ApiResource(
 * denormalizationContext={"groups"={"compte:write"}},
 * normalizationContext={"groups"={"compte:read"}},
 * 
 *    collectionOperations={
 *        "get"={"access_control"="is_granted('ROLE_AdminSystem') or is_granted('ROLE_Caissier')"},
 *        "post"={"access_control"="is_granted('ROLE_AdminSystem')"}  
 *},
 *    itemOperations={
 *        "get"={"access_control"="is_granted('ROLE_AdminSystem')or is_granted('ROLE_Caissier')"},
 *         "delete"={"access_control"="is_granted('ROLE_AdminSystem')"}
 *})
 *
 * @ORM\Entity(repositoryClass=CompteRepository::class)
 * @UniqueEntity("numeroCompte", message="l'adress username doit être unique")
 */
class Compte
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"compte:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Asset\NotBlank(message="Veuillez remplir ce champs")
     * @Groups({"compte:write","compte:read"})
     */
    private $numeroCompte;

    /**
     * @ORM\Column(type="float", precision=10, scale=0)
     * @Asset\NotBlank(message="Veuillez remplir ce champs")
     * @Groups({"compte:write","compte:read"})
     */
    private $montant;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"compte:write","compte:read"})
     * @Asset\NotBlank(message="Veuillez remplir ce champs")
     */
    private $statut = false;

    /**
     * @ORM\OneToOne(targetEntity=Agence::class, mappedBy="compte", cascade={"persist", "remove"})
     * @Asset\NotBlank(message="Veuillez remplir ce champs")
     * @Groups({"compte:write","compte:read"})
     */
    private $agence;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCompte(): ?string
    {
        return $this->numeroCompte;
    }

    public function setNumeroCompte(string $numeroCompte): self
    {
        $this->numeroCompte = $numeroCompte;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): self
    {
        // unset the owning side of the relation if necessary
        if ($agence === null && $this->agence !== null) {
            $this->agence->setCompte(null);
        }

        // set the owning side of the relation if necessary
        if ($agence !== null && $agence->getCompte() !== $this) {
            $agence->setCompte($this);
        }

        $this->agence = $agence;

        return $this;
    }
}