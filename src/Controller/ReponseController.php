<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Reponse;
use App\Form\ReponseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class ReponseController extends AbstractController
{
    #[Route('/messages', name: 'reponse_messages')]
    public function index(EntityManagerInterface $em): Response
    {
        $messages = $em->getRepository(Message::class)->findAll();

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/reply/new/{id}', name: 'reply_new')]
    public function new(Request $request, EntityManagerInterface $em, Message $message): Response
    {
        $reponse = new Reponse();
        $reponse->setMessage($message);

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reponse);
            $em->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès.');
            return $this->redirectToRoute('reponse_messages');
        }

        return $this->render('admin/reply_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reply/{id}', name: 'reply_show')]
    public function show(Reponse $reponse): Response
    {
        return $this->render('admin/reply_show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/reply/{id}/edit', name: 'reply_edit')]
    public function edit(Request $request, EntityManagerInterface $em, Reponse $reponse): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Réponse modifiée avec succès.');
            return $this->redirectToRoute('reponse_messages');
        }

        return $this->render('admin/reply_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reply/{id}/delete', name: 'reply_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em, Reponse $reponse): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();

            $this->addFlash('danger', 'Réponse supprimée.');
        }

        return $this->redirectToRoute('reponse_messages');
    }

    #[Route('/reply/{id}/mark-read', name: 'reply_mark_read')]
    public function markAsRead(EntityManagerInterface $em, Reponse $reponse): Response
    {
        if (!$reponse->isRead()) {
            $reponse->setIsRead(true);
            $em->flush();

            $this->addFlash('info', 'Réponse marquée comme lue.');
        }

        return $this->redirectToRoute('reponse_messages');
    }
}
