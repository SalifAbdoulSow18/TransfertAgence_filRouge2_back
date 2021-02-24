<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

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
       $transactions = $serializerInterface->denormalize($data, "App\Entity\Transaction");
       $transactions->setDateDepot(new \DateTime());
       $transactions->setCodeTransaction($this->genereCodeTransaction());
       $transactions->setUserDepot($this->getUser());
       $transactions->setFraisTotal(1500);
       $transactions->setFraisEtat($this->calculPart(40, $transactions->getFraisTotal()));
       $transactions->setFraisSystem($this->calculPart(30, $transactions->getFraisTotal()));
       $transactions->setFraisEnvoi($this->calculPart(10, $transactions->getFraisTotal()));
       $transactions->setFraisRetrait($this->calculPart(20, $transactions->getFraisTotal()));
       //dd($transactions);
       $manager->persist($transactions);
       $manager->flush();
       return $this->json(['message' => 'Succes', 'data'=>$transactions]);

    }

    // -------------------------------------------------Pour le retrait
     /**
     *  @Route(
     *  "api/transactions",
     *   name="retrait",
     *   methods={"POST"}
     * )
     */
    public function retrait()
    {
       
    }

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
