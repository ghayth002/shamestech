<?php

namespace App\Controller;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    #[Route('/messages', name: 'app_messages')]
    public function index(EntityManagerInterface $em): Response
    {
        $messages = $em->getRepository(Message::class)->findAll();

        return $this->render('message/index.html.twig', [
            'messages' => $messages,
        ]);
    }
}
