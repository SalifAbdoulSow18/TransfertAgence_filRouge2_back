<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TransactionRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 * normalizationContext={"groups"={"transaction:read"}},
 * 
 *    collectionOperations={
 *  "get"
 *},
 *    itemOperations={
 *      "get"
 *})
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"transaction:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $montant;

    /**
     * @ORM\Column(type="date")
     * @Groups({"transaction:read"})
     */
    private $dateDepot;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"transaction:read"})
     */
    private $dateRetrait;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $fraisTotal;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $fraisEtat;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $fraisSystem;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $fraisEnvoi;

    /**
     * @ORM\Column(type="float")
     * @Groups({"transaction:read"})
     */
    private $fraisRetrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"transaction:read"})
     */
    private $codeTransaction;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"transaction:read"})
     */
    private $userDepot;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="transactions")
     * @Groups({"transaction:read"})
     */
    private $userRetrait;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="transactions", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"transaction:read"})
     */
    private $clientDepot;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="transactions", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"transaction:read"})
     */
    private $clientRetrait;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateDepot(): ?\DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function setDateDepot(\DateTimeInterface $dateDepot): self
    {
        $this->dateDepot = $dateDepot;

        return $this;
    }

    public function getDateRetrait(): ?\DateTimeInterface
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(?\DateTimeInterface $dateRetrait): self
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    public function getFraisTotal(): ?float
    {
        return $this->fraisTotal;
    }

    public function setFraisTotal(float $fraisTotal): self
    {
        $this->fraisTotal = $fraisTotal;

        return $this;
    }

    public function getFraisEtat(): ?float
    {
        return $this->fraisEtat;
    }

    public function setFraisEtat(float $fraisEtat): self
    {
        $this->fraisEtat = $fraisEtat;

        return $this;
    }

    public function getFraisSystem(): ?float
    {
        return $this->fraisSystem;
    }

    public function setFraisSystem(float $fraisSystem): self
    {
        $this->fraisSystem = $fraisSystem;

        return $this;
    }

    public function getFraisEnvoi(): ?float
    {
        return $this->fraisEnvoi;
    }

    public function setFraisEnvoi(float $fraisEnvoi): self
    {
        $this->fraisEnvoi = $fraisEnvoi;

        return $this;
    }

    public function getFraisRetrait(): ?float
    {
        return $this->fraisRetrait;
    }

    public function setFraisRetrait(float $fraisRetrait): self
    {
        $this->fraisRetrait = $fraisRetrait;

        return $this;
    }

    public function getCodeTransaction(): ?string
    {
        return $this->codeTransaction;
    }

    public function setCodeTransaction(string $codeTransaction): self
    {
        $this->codeTransaction = $codeTransaction;

        return $this;
    }

    public function getUserDepot(): ?User
    {
        return $this->userDepot;
    }

    public function setUserDepot(?User $userDepot): self
    {
        $this->userDepot = $userDepot;

        return $this;
    }

    public function getUserRetrait(): ?User
    {
        return $this->userRetrait;
    }

    public function setUserRetrait(?User $userRetrait): self
    {
        $this->userRetrait = $userRetrait;

        return $this;
    }

    public function getClientDepot(): ?Client
    {
        return $this->clientDepot;
    }

    public function setClientDepot(?Client $clientDepot): self
    {
        $this->clientDepot = $clientDepot;

        return $this;
    }

    public function getClientRetrait(): ?Client
    {
        return $this->clientRetrait;
    }

    public function setClientRetrait(?Client $clientRetrait): self
    {
        $this->clientRetrait = $clientRetrait;

        return $this;
    }
 // pour la recuperation des tarifs.
    public function calculeFraisTotal(){
        switch (true) {
            case ($this->montant<=5000):
                $this->fraisTotal = 425;
                break;
            case ($this->montant<=10000 && $this->montant > 5000):
                $this->fraisTotal = 850;
                break;
            case ($this->montant<=15000 && $this->montant > 10000):
                $this->fraisTotal = 1270;
                break;
            case ($this->montant<=20000 && $this->montant > 15000):
                $this->fraisTotal = 1695;
                break;
            case ($this->montant<=50000 && $this->montant > 20000):
                $this->fraisTotal = 2500;
                break;    
            case ($this->montant<=60000 && $this->montant > 50000):
                $this->fraisTotal = 3000;
                break;
            case ($this->montant<=75000 && $this->montant > 60000):
                $this->fraisTotal = 4000;
                    break;
            case ($this->montant<=120000 && $this->montant > 75000):
                $this->fraisTotal = 5000;    
                break;
            case ($this->montant<=150000 && $this->montant > 120000):
                $this->fraisTotal = 6000;
                break;
            case ($this->montant<=200000 && $this->montant > 150000):
                $this->fraisTotal = 7000;
                break;                 
            case ($this->montant<=250000 && $this->montant > 200000):
                $this->fraisTotal = 8000;
                break;
            case ($this->montant<=300000 && $this->montant > 250000):
                $this->fraisTotal = 90000;
                break;
            case ($this->montant<=400000 && $this->montant > 300000):
                $this->fraisTotal = 12000;
                break;
            case ($this->montant<=750000 && $this->montant > 400000):
                $this->fraisTotal = 15000;
                break;        
        
            case ($this->montant<=900000 && $this->montant > 750000):
                $this->fraisTotal = 22000;
                break; 
                        
            case ($this->montant<=1000000 && $this->montant > 900000):
                $this->fraisTotal = 25000;
                break;
            case ($this->montant<=1125000 && $this->montant > 1000000):
                $this->fraisTotal = 27000;
                break;
            case ($this->montant<=14000000 && $this->montant > 1125000):
                $this->fraisTotal = 30000;
                break;
            case ($this->montant<=20000000 && $this->montant > 14000000):
                $this->fraisTotal = 30000;
                break;
            case ($this->montant > 20000000):
                $this->fraisTotal = (2 * $this->montant) / 100;
                break;                         
        }
    }
}
