<?php

namespace App\Controller;

use App\Entity\Depot;
use App\Repository\CommissionRepository;
use App\Repository\CompteRepository;
use App\Repository\DepotRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\TableauFraisRepository;
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
    public function depot(SerializerInterface $serializerInterface, Request $request, EntityManagerInterface $manager, TableauFraisRepository $monney, CommissionRepository $commission)
    { 
       $data = json_decode($request->getContent(), true);
       if (!$this->getUser() || $this->getUser()->getAgence() === null) {
        return $this->json(['message' => 'Accès non autorisé'], 403);
       }
       if ($this->getUser()->getAgence()->getCompte()->getMontant() < 5000 || $this->getUser()->getAgence()->getCompte()->getMontant() < $data['montant']) {
        return $this->json(['message' => 'Vous n \'avez assez d\'argent sur votre compte'], 401);
       }
       $part = $commission->findAll();
        foreach ($part as $value) {
            $partChacun = $value;       
        }
       $transactions = $serializerInterface->denormalize($data, "App\Entity\Transaction");
       $transactions->setDateDepot(new \DateTime());
       $transactions->setCodeTransaction($this->genereCodeTransaction());
       $transactions->calculeFraisTotal($monney);
       $transactions->setFraisEtat($this->calculPart($partChacun->getCommissionEtat(), $transactions->getFraisTotal()));
       $transactions->setFraisSystem($this->calculPart($partChacun->getCommissionSystem(), $transactions->getFraisTotal()));
       $transactions->setFraisEnvoi($this->calculPart($partChacun->getCommissionEnvoie(), $transactions->getFraisTotal()));
       $transactions->setFraisRetrait($this->calculPart($partChacun->getCommissionRetrait(), $transactions->getFraisTotal()));
       $montantRetire = $transactions->getMontant() - $transactions->getFraisTotal();
       $transactions->setMontantRetrait($montantRetire);
       //dd($montantRetire);
       $restMontant = $this->getUser()->getAgence()->getCompte()->getMontant() - $transactions->getMontant() + $transactions->getFraisEnvoi();
       $this->getUser()->getAgence()->getCompte()->setMontant($restMontant);
       $transactions->setUserDepot($this->getUser());
       //dd($transactions);
       $manager->persist($transactions);
       $manager->flush();
       return $this->json(['message' => 'Succes', 'data'=>$transactions]);

    }

    // -------------------------Annulation de depot par un caisier ou AdminSystem

    /**
     * @Route(
     *  "api/transaction/annuler/{id}",
     *   name="annulerTransaction",
     *   methods={"DELETE"}
     * )
     */
    public function annulerTransaction(EntityManagerInterface $manager, TransactionRepository $repo, CommissionRepository $commission, $id) {
        if (!$this->isGranted('ROLE_UserAgence') && !$this->isGranted('ROLE_AdminAgence')) {
            return $this->json(['message' => 'Accès non autorisé'], 401);
        }
        $compte = $repo->find($id);
        $part = $commission->findAll();
        foreach ($part as $value) {
            $partChacun = $value;       
        }
        $montantEnvoi = $compte->getMontant();
        $compteEnvoi = $compte->getUserDepot()->getAgence()->getCompte();
        $sommeAnnulation = $this->calculPart($partChacun->getCommissionAgence(), $compte->getFraisTotal());
        $prixRetrait = $this->calculPart($partChacun->getCommissionRetrait(), $compte->getFraisTotal());
        //dd($sommeAnnulation);
        
        $newSoldeCompte =($compteEnvoi->getMontant() + $montantEnvoi + $prixRetrait);
        $compteEnvoi->setMontant($newSoldeCompte);
        $compte->setMontantAnnulation($montantEnvoi - $sommeAnnulation);
        $compte->setFraisSystem(0);
        $compte->setFraisEtat(0);
        $compte->setMontantRetrait(0);
        $compte->setFraisTotal($sommeAnnulation);
        $compte->setDateAnnulation(new \DateTime());
        $compte->setStatut(true);
        //dd($compte);
        $manager->flush();
        return $this->json(['message' => 'annulation réussir!!!']);

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
        $restMontant = $this->getUser()->getAgence()->getCompte()->getMontant() + $transactions->getMontant() - $transactions->getFraisTotal() + $transactions->getFraisRetrait();
    
        $this->getUser()->getAgence()->getCompte()->setMontant($restMontant);
        $transactions->setDateRetrait(new \DateTime());
        $transactions->setUserRetrait($this->getUser());
        //dd($restMontant);
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


    // ----------------------------------------Pour le rechargement d'un compte d'une Agence
     /**
     *  @Route(
     *  "api/rechargeComptes/{id}",
     *   name="rechargeCompte",
     *   methods={"PUT"}
     * )
     */
    public function rechargeCompte(Request $request, EntityManagerInterface $manager, CompteRepository $repo,int $id)
    {
        $data = json_decode($request->getContent(), true);
        //dd($data);
        if (!$this->getUser() || (!in_array('ROLE_AdminSystem', $this->getUser()->getRoles()) && !in_array('ROLE_Caissier', $this->getUser()->getRoles()))) {
         return $this->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($data['montantDepot'] < 0) {
            return $this->json(['message' => 'Vous ne pouvez pas retirer du cash sur ce compte'], 401);
        }
        $compte = $repo->find($id);
        if (!$compte) {
            return $this->json(['message' => 'Le compte n\'existe pas'], 401);
        }
        $newMontantCompte = $compte->getMontant() + $data['montantDepot'];
        $compte->setMontant($newMontantCompte);
        $depot = new Depot();
        $depot->setMontantDepot($data['montantDepot']);
        $depot->setUserDepot($this->getUser());
        $depot->setDateDepot(new \DateTime());
        $compte->addDepot($depot);
        $manager->flush();
        return $this->json(['message' => 'Succes', 'data'=>$compte]);
        
    } 

   
    // -------------------------Annulation de depot par un caisier ou AdminSystem

    /**
     * @Route(
     *  "api/rechargeComptes/annuler",
     *   name="annulerDepot",
     *   methods={"DELETE"}
     * )
     */
    public function annulerDepot(EntityManagerInterface $manager, DepotRepository $depotRepository) {
        if ($this->isGranted('ROLE_Caissier')) {
            $lastDepot = $depotRepository->findOneBy([], ['id' => 'desc']);
            //dd($lastDepot);
            $lastMontantEnvoi = $lastDepot->getMontantDepot();
            $lastCompteEnvoi = $lastDepot->getCompte();
            //dd($lastCompteEnvoi);
            // recuperation du montant de depot dans le compte de l'agence
            if ($lastCompteEnvoi->getMontant() < $lastMontantEnvoi) {
                return $this->json(['message' => 'Vous ne pouvez pas annuler'], 401);
               }
            $newMontantCompte = $lastCompteEnvoi->getMontant() - $lastMontantEnvoi;
            $lastCompteEnvoi->setMontant($newMontantCompte);
            // nous allons supprimer la ligne du prends les info de la table d'associaton
            $manager->remove($lastDepot);
            $manager->flush();
            return $this->json(['message' => 'annulation réussir!!!']);
        }else {
            return $this->json("Vous n'avez pas accès !!!");
        }
    }
}

