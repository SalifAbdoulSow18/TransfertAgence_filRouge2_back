<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TransactionController extends AbstractController
{
    /**
     *  @Route(
     *  "api/transactions/depot",
     *   name="depot",
     *   methods={"POST"}
     * )
     */
    public function depot(SerializerInterface $serializerInterface, Request $request, EntityManagerInterface $manager)
    { 
       $data = json_decode($request->getContent(), true);
       if (!$this->getUser() || $this->getUser()->getAgence() === null) {
        return $this->json(['message' => 'Accès non autorisé'], 403);
       }
       if ($this->getUser()->getAgence()->getCompte()->getMontant() < 5000 || $this->getUser()->getAgence()->getCompte()->getMontant() < $data['montant']) {
        return $this->json(['message' => 'Vous n \'avez assez d\'argent sur votre compte'], 401);
       }
       
       $transactions = $serializerInterface->denormalize($data, "App\Entity\Transaction");
       $transactions->setDateDepot(new \DateTime());
       $transactions->setCodeTransaction($this->genereCodeTransaction());
       $transactions->calculeFraisTotal();
       $transactions->setFraisEtat($this->calculPart(40, $transactions->getFraisTotal()));
       $transactions->setFraisSystem($this->calculPart(30, $transactions->getFraisTotal()));
       $transactions->setFraisEnvoi($this->calculPart(10, $transactions->getFraisTotal()));
       $transactions->setFraisRetrait($this->calculPart(20, $transactions->getFraisTotal()));
       $restMontant = $this->getUser()->getAgence()->getCompte()->getMontant() - $transactions->getMontant();
        $this->getUser()->getAgence()->getCompte()->setMontant($restMontant);
        $transactions->setUserDepot($this->getUser());

       //dd($transactions);
       $manager->persist($transactions);
       $manager->flush();
       return $this->json(['message' => 'Succes', 'data'=>$transactions]);

    }

    // -------------------------------------------------Pour le retrait
     /**
     *  @Route(
     *  "api/transactions/retrait",
     *   name="retrait",
     *   methods={"POST"}
     * )
     */
    public function retrait(Request $request, EntityManagerInterface $manager, TransactionRepository $repo)
    {
        $data = json_decode($request->getContent(), true);
        if (!$this->getUser() || $this->getUser()->getAgence() === null) {
         return $this->json(['message' => 'Accès non autorisé'], 403);
        }
        $transactions = $repo->findOneByCodeTransaction($data['codeTransaction']);
        if ($transactions->getDateRetrait()) {
            return $this->json(['message' => 'Vous avez déjà recuperé votre argent'], 401);
        }
        if ($this->getUser()->getAgence()->getCompte()->getMontant() < $transactions->getMontant()) {
         return $this->json(['message' => 'Vous n \'avez assez d\'argent sur votre compte'], 401);
        }
        if (!$data['clientRetrait'] || $transactions->getClientRetrait()->getPhone() !== $data['clientRetrait']) {
            return $this->json(['message' => 'les informations du client ne correspondent pas!!!'], 401);
        } 
        $restMontant = $this->getUser()->getAgence()->getCompte()->getMontant() - $transactions->getMontant();
    
        $this->getUser()->getAgence()->getCompte()->setMontant($restMontant);
        $transactions->setDateRetrait(new \DateTime());
        $transactions->setUserRetrait($this->getUser());
        
        //$manager->persist($transactions);
        $manager->flush();
        return $this->json(['message' => 'Succes', 'data'=>$transactions]);

        
    }

    // calcul des parts
    public function calculPart($pourcent, $montant)
    {
        return ($pourcent*$montant)/100;
    }

    // pour generer aleatoirement les codes de transaction
    public function genereCodeTransaction($longueur=6) {
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longueurMax = strlen($caracteres);
        $chaineAleatoire = '';
        for ($i = 0; $i < $longueur; $i++)
        {
        $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
        }
        return $chaineAleatoire;
    }

   
    
}

